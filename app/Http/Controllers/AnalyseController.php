<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Http;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Analyse;

class AnalyseController extends Controller
{
    public function analyse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $response = Http::get($request->url);
        $html = $response->body();
        if (strpos($html, '<html') === false) {
            return redirect()->back()->withErrors(['url' => 'The URL is not a valid HTML page'])->withInput();
        }
        
        $article = preg_match('/<article class="article-pcr">(.*?)<\/article>/s', $html, $matches);
        if ($article === false || $article === 0) {
            return redirect()->back()->withErrors(['url' => 'No content found on this URL'])->withInput();
        }

        $matches[0] = preg_replace('/\n/', '', $matches[0]);
        preg_match('/<h1>(.*?)<\/h1>/', $matches[0], $h1);
        $title = $h1[1];

        preg_match_all('/<ul class="main-list">(.*?)<\/ul>/', $matches[0], $ul);
        $goals = [];
        foreach ($ul[1] as $li) {
            preg_match_all('/<li>(.*?)<\/li>/', $li, $li);
            foreach ($li[1] as $li) {
                $li = preg_replace('/\r/', '', $li);
                $goals[] = strip_tags($li);
            }
        }
        
        preg_match('/<div class="resource_teaser_block">(.*?)<\/div>/', $matches[0], $div);
        preg_match('/<ul>(.*?)<\/ul>/', $div[0], $ul);
        preg_match_all('/<li>(.*?)<\/li>/', $ul[0], $lis);
        $resources = [];
        foreach ($lis[1] as $li) {
            $li = preg_replace('/\r/', '', $li);
            $resources[] = strip_tags($li);
        }
        $chatResponse = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo-0125',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            "type" => "text",
                            "text" => "I would like you to make a textual summary of the session with the following information: 
                                The title of the session is: " . $title . ";\n
                                The goals of the session are: " . implode(', ', $goals) . ";\n
                                The resources of the session are: " . implode(', ', $resources) . ";\n
                                Here is an example of summary That i would like you to generate: 'In this session, discover strategies to address biases in aortic regurgitation, influenced by factors like etiology, natural history, surgical risk, age, and gender, and explore an imaging-centric approach to better quantify aortic regurgitation and comprehend its relationship with left ventricular remodeling and outcomes. Anticipate forthcoming guidelines that may introduce alternative management options for high surgical risk patients.'",
                        ],
                    ]
                ],
            ],
        ]);
        $summaryRexponse = $chatResponse->choices[0]->message->content;
        $summaryTokenIn = $chatResponse->usage->promptTokens;
        $summaryTokenOut = $chatResponse->usage->completionTokens;
        $summaryTokenTotal = $chatResponse->usage->totalTokens;

        $name = md5(microtime());
        BrowserShot::url($request->url)
        ->setChromePath('/root/.cache/puppeteer/chrome/linux-119.0.6045.105/chrome-linux64/chrome')
        ->useCookies([
            'axeptio_all_vendors' => '%2Cgoogle_analytics%2Cmatomo%2Clinkedin%2Ctwitter%2Cvimeo%2Cyoutube%2Cfacebook_pixel%2Cgoogle_ads%2Cslido%2CMatomo%2CLinkedin%2CTwitter%2CYoutube%2CGoogle_Ads%2C',
            'axeptio_authorized_vendors' => '%2Cgoogle_analytics%2Cmatomo%2Clinkedin%2Ctwitter%2Cvimeo%2Cyoutube%2Cfacebook_pixel%2Cgoogle_ads%2Cslido%2CMatomo%2CLinkedin%2CTwitter%2CYoutube%2CGoogle_Ads%2C',
            'axeptio_cookies' => '{%22$$token%22:%22of7ygq32bfrybpd0b55e%22%2C%22$$date%22:%222024-01-09T10:38:05.796Z%22%2C%22$$cookiesVersion%22:{%22name%22:%22pcronline-en%22%2C%22identifier%22:%2263fcc982fc0e5dc395ebcba4%22}%2C%22google_analytics%22:true%2C%22matomo%22:true%2C%22linkedin%22:true%2C%22twitter%22:true%2C%22vimeo%22:true%2C%22youtube%22:true%2C%22facebook_pixel%22:true%2C%22google_ads%22:true%2C%22slido%22:true%2C%22Matomo%22:true%2C%22Linkedin%22:true%2C%22Twitter%22:true%2C%22Youtube%22:true%2C%22Google_Ads%22:true%2C%22$$completed%22:true}',
        ])
        ->fullPage()
        ->save(storage_path('app/public/screenshots/' . $name . '.png'));

        $imageBase64 = base64_encode(file_get_contents(storage_path('app/public/screenshots/' . $name . '.png')));
        $visionResponse = OpenAI::chat()->create([
            'model' => 'gpt-4-vision-preview',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            "type" => "text",
                            "text" => "I would like to analyse this screenshot",
                        ],
                        [
                            "type" => "image_url",
                            "image_url" => [
                                "url" => "data:image/jpeg;base64," . $imageBase64,
                            ],
                        ],
                    ]
                ],
            ],
            'max_tokens' => 2048
        ]);
        $analyseResponse = $visionResponse->choices[0]->message->content;
        $analyseTokenIn = $visionResponse->usage->promptTokens;
        $analyseTokenOut = $visionResponse->usage->completionTokens;
        $analyseTokenTotal = $visionResponse->usage->totalTokens;

        $analysis = new Analyse();
        $analysis->url = $request->url;
        $analysis->title = $title;
        $analysis->goals = json_encode($goals);
        $analysis->resources = json_encode($resources);
        $analysis->summary = $summaryRexponse;
        $analysis->image = $name . '.png';
        $analysis->image_analyse = $analyseResponse;
        $analysis->summary_tokens_in = $summaryTokenIn;
        $analysis->summary_tokens_out = $summaryTokenOut;
        $analysis->summary_tokens_total = $summaryTokenTotal;
        $analysis->image_analyse_tokens_in = $analyseTokenIn;
        $analysis->image_analyse_tokens_out = $analyseTokenOut;
        $analysis->image_analyse_tokens_total = $analyseTokenTotal;
        $analysis->save();

        return redirect()->back()->with('success', 'Screenshot saved');
    }
    public function show()
    {
        return view('form');
    }
}
