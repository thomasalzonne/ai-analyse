<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Spatie\Browsershot\Browsershot;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Analyse;

class AnalyseController extends Controller
{
    public function analyse(Request $request)
    {
        $validator = $this->validateRequest($request);

        if ($validator->fails()) {
            return $this->redirectToFormWithError($validator);
        }

        $html = $this->fetchHtml($request->url);

        if (!$this->isValidHtml($html)) {
            return $this->redirectToFormWithError(['url' => 'The URL is not a valid HTML page']);
        }

        $article = $this->extractArticle($html);

        if (!$article) {
            return $this->redirectToFormWithError(['url' => 'No content found on this URL']);
        }

        [$title, $goals, $resources] = $this->extractContent($article);

        [$summaryResponse, $summaryTokenIn, $summaryTokenOut, $summaryTokenTotal] = $this->generateSummary($title, $goals, $resources);
        $imageFilename = $this->captureScreenshot($request->url);
        $imageBase64 = $this->convertImageToBase64($imageFilename);
        [$analyseResponse, $analyseTokenIn, $analyseTokenOut, $analyseTokenTotal] = $this->analyzeImage($imageBase64);

        $this->saveAnalysis($request->url, $title, $goals, $resources, $summaryResponse, $imageFilename, $analyseResponse);

        return redirect()->back()->with('success', 'Screenshot saved');
    }

    public function show()
    {
        return view('form');
    }

    private function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'url' => 'required|url',
        ]);
    }

    private function redirectToFormWithError($validator)
    {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    private function fetchHtml($url)
    {
        $response = Http::get($url);
        return $response->body();
    }

    private function isValidHtml($html)
    {
        return strpos($html, '<html') !== false;
    }

    private function extractArticle($html)
    {
        preg_match('/<article class="article-pcr">(.*?)<\/article>/s', $html, $matches);

        if(isset($matches[0])){
            $matches[0] = preg_replace('/\r/', '', $matches[0]);
            $matches[0] = preg_replace('/\n/', '', $matches[0]);
            return $matches[0] ?? false;
        }
        return false;
    }

    private function extractContent($article)
    {
        $matches = [];
        preg_match('/<h1>(.*?)<\/h1>/', $article, $h1);
        $title = $h1[1] ?? '';
    
        preg_match_all('/<ul class="main-list">(.*?)<\/ul>/', $article, $ul);
        $goals = [];
        if(isset($ul[1])){
            foreach ($ul[1] as $li) {
                preg_match_all('/<li>(.*?)<\/li>/', $li, $li);
                foreach ($li[1] as $li) {
                    $li = preg_replace('/\r/', '', $li);
                    $goals[] = strip_tags($li);
                }
            }
        }
    
        preg_match('/<div class="resource_teaser_block">(.*?)<\/div>/', $article, $div);
    
        if (isset($div[0])) {
            preg_match('/<ul>(.*?)<\/ul>/', $div[0], $ul);
            preg_match_all('/<li>(.*?)<\/li>/', $ul[0], $lis);
            $resources = [];
            foreach ($lis[1] as $li) {
                $li = preg_replace('/\r/', '', $li);
                $resources[] = strip_tags($li);
            }
        } else {
            preg_match_all('/<p class="txt-panel">(.*?)<\/p>/', $article, $panels);
            $resources = [];
            if(isset($panels[1])){
                foreach ($panels[1] as $panel) {
                    preg_match_all('/<span class="ezstring-field">(.*?)<\/span>/', $panel, $spans);
                    foreach ($spans[1] as $span) {
                        $span = preg_replace('/\r/', '', $span);
                        $resources[] = strip_tags($span);
                    }
                }
            }
        }
        return [$title, $goals, $resources];
    }
    

    private function generateSummary($title, $goals, $resources)
    {
        $message = "I would like you to make a textual summary of the session with the following 
        information: \nThe title of the session is: $title;\n The goals of the session 
        are: " . implode(', ', $goals) . ";\n The resources of the session are: 
        " . implode(', ', $resources) . ";\n Here is an example of summary That i would like you 
        to generate: 'In this session, discover strategies to address biases in aortic regurgitation, 
        influenced by factors like etiology, natural history, surgical risk, age, and gender, and 
        explore an imaging-centric approach to better quantify aortic regurgitation and comprehend 
        its relationship with left ventricular remodeling and outcomes. Anticipate forthcoming 
        guidelines that may introduce alternative management options for high surgical risk patients. 
        I don't want you to list the goals and resources, but to generate a summary based on them.";
        
        $chatResponse = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo-0125',
            'messages' => [['role' => 'user', 'content' => [['type' => 'text', 'text' => $message]]]],
        ]);

        $summaryTokenIn = $chatResponse->usage->promptTokens;
        $summaryTokenOut = $chatResponse->usage->completionTokens;
        $summaryTokenTotal = $chatResponse->usage->totalTokens;
        $summaryResponse = $chatResponse->choices[0]->message->content;
        return [$summaryResponse, $summaryTokenIn, $summaryTokenOut, $summaryTokenTotal];
    }

    private function captureScreenshot($url)
    {
        $name = md5(microtime());
        Browsershot::url($url)
            ->setChromePath('/root/.cache/puppeteer/chrome/linux-119.0.6045.105/chrome-linux64/chrome')
            ->useCookies([
                'axeptio_all_vendors' => '%2Cgoogle_analytics%2Cmatomo%2Clinkedin%2Ctwitter%2Cvimeo%2Cyoutube%2Cfacebook_pixel%2Cgoogle_ads%2Cslido%2CMatomo%2CLinkedin%2CTwitter%2CYoutube%2CGoogle_Ads%2C',
                'axeptio_authorized_vendors' => '%2Cgoogle_analytics%2Cmatomo%2Clinkedin%2Ctwitter%2Cvimeo%2Cyoutube%2Cfacebook_pixel%2Cgoogle_ads%2Cslido%2CMatomo%2CLinkedin%2CTwitter%2CYoutube%2CGoogle_Ads%2C',
                'axeptio_cookies' => '{%22$$token%22:%22of7ygq32bfrybpd0b55e%22%2C%22$$date%22:%222024-01-09T10:38:05.796Z%22%2C%22$$cookiesVersion%22:{%22name%22:%22pcronline-en%22%2C%22identifier%22:%2263fcc982fc0e5dc395ebcba4%22}%2C%22google_analytics%22:true%2C%22matomo%22:true%2C%22linkedin%22:true%2C%22twitter%22:true%2C%22vimeo%22:true%2C%22youtube%22:true%2C%22facebook_pixel%22:true%2C%22google_ads%22:true%2C%22slido%22:true%2C%22Matomo%22:true%2C%22Linkedin%22:true%2C%22Twitter%22:true%2C%22Youtube%22:true%2C%22Google_Ads%22:true%2C%22$$completed%22:true}',
            ])
            ->fullPage()
            ->save(storage_path('app/public/screenshots/' . $name . '.png'));

        return $name . '.png';
    }

    private function convertImageToBase64($filename)
    {
        return base64_encode(file_get_contents(storage_path('app/public/screenshots/' . $filename)));
    }

    private function analyzeImage($imageBase64)
    {
        $visionResponse = OpenAI::chat()->create([
            'model' => 'gpt-4-vision-preview',
            'messages' => [['role' => 'user', 'content' => [
                ['type' => 'text', 'text' => 'I would like to analyse this screenshot and give me a review of what could be improved on this page to have more reach.',],
                ['type' => 'image_url', 'image_url' => ['url' => "data:image/jpeg;base64,$imageBase64"]]
                ]]],
            'max_tokens' => 2048
        ]);
        $analyseTokenIn = $visionResponse->usage->promptTokens;
        $analyseTokenOut = $visionResponse->usage->completionTokens;
        $analyseTokenTotal = $visionResponse->usage->totalTokens;
        $analyse = $visionResponse->choices[0]->message->content;
        return [$analyse, $analyseTokenIn, $analyseTokenOut, $analyseTokenTotal];
    }

    private function saveAnalysis($url, $title, $goals, $resources, $summaryResponse, $imageFilename, $analyseResponse)
    {
        $analysis = new Analyse();
        $analysis->url = $url;
        $analysis->title = $title;
        $analysis->goals = json_encode($goals);
        $analysis->resources = json_encode($resources);
        $analysis->summary = $summaryResponse;
        $analysis->image = $imageFilename;
        $analysis->image_analyse = $analyseResponse;
        $analysis->summary_token_in = $summaryTokenIn;
        $analysis->summary_token_out = $summaryTokenOut;
        $analysis->summary_token_total = $summaryTokenTotal;
        $analysis->analyse_token_in = $analyseTokenIn;
        $analysis->analyse_token_out = $analyseTokenOut;
        $analysis->analyse_token_total = $analyseTokenTotal;
        $analysis->save();
    }
}
