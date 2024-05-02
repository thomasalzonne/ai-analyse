<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Spatie\Browsershot\Browsershot;
use OpenAI\Laravel\Facades\OpenAI;
use App\Models\Analyse;
use App\Models\XmlSummary;
use App\Facades\AiFacade;
use SimpleXMLElement;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use Illuminate\Support\Sleep;

class AnalyseController extends Controller
{
    public function analyse(Request $request)
    {
        $validator = $this->validateRequest($request);

        if ($validator->fails()) {
            return $this->redirectToFormWithError($validator);
        }

        if($request->type === 'page'){
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
    
            $this->saveAnalysis($request->url, $title, $goals, $resources, $summaryResponse, $imageFilename, $analyseResponse, $summaryTokenIn, $summaryTokenOut, $summaryTokenTotal, $analyseTokenIn, $analyseTokenOut, $analyseTokenTotal);
    
            return redirect()->back()->with('success', 'Screenshot saved');
        }
        elseif($request->type === 'xml'){
            $xmlContent = file_get_contents($request->url);
            $xml = new DOMDocument();
            $xml->loadXML($xmlContent);

            $sessionsData = [];
            $topicsData = $xml->getElementsByTagName('topics')->item(0); // Première occurrence de la balise topics
            $specialitiesData = $xml->getElementsByTagName('specialities')->item(0); // Première occurrence de la balise specialities

            foreach ($xml->getElementsByTagName('session') as $session) {
                $sessionData = [
                    'id' => $session->getAttribute('id'),
                    'title' => $session->getElementsByTagName('title')->item(0)->nodeValue,
                    'subtitle' => $session->getElementsByTagName('subtitle')->item(0)->nodeValue,
                    'objectives' => [],
                    'topics' => [],
                    'specialities' => [],
                    'interventions' => []
                ];

                // Objectifs
                $objectiveElements = $session->getElementsByTagName('objective');
                foreach ($objectiveElements as $objective) {
                    $sessionData['objectives'][] = $objective->nodeValue;
                }

                // Topics
                $topicsElement = $session->getElementsByTagName('topics')->item(0);
                if ($topicsElement) {
                    $topicsIds = explode('|', $topicsElement->nodeValue);
                    foreach ($topicsIds as $topicId) {
                        $topicName = $this->getTopicNameById($topicsData, $topicId);
                        if ($topicName) {
                            $sessionData['topics'][] = [
                                'id' => $topicId,
                                'name' => $topicName
                            ];
                        }
                    }
                }

                // Spécialités
                $specialitiesElement = $session->getElementsByTagName('specialities')->item(0);
                if ($specialitiesElement) {
                    $specialitiesIds = explode('|', $specialitiesElement->nodeValue);
                    foreach ($specialitiesIds as $specialityId) {
                        $specialityName = $this->getSpecialityNameById($specialitiesData, $specialityId);
                        if ($specialityName) {
                            $sessionData['specialities'][] = [
                                'id' => $specialityId,
                                'name' => $specialityName
                            ];
                        }
                    }
                }

                // Interventions
                $interventionElements = $session->getElementsByTagName('intervention');
                foreach ($interventionElements as $intervention) {
                    $interventionData = [
                        'title' => $intervention->getElementsByTagName('title')->item(0)->nodeValue,
                        'speakers' => []
                    ];
                    $speakerElements = $intervention->getElementsByTagName('speaker');
                    foreach ($speakerElements as $speaker) {
                        $speakerId = $speaker->getAttribute('id');
                        $speakerName = $this->getSpeakerNameById($xml->getElementsByTagName('physicians')->item(0), $speakerId);
                        if ($speakerName) {
                            $interventionData['speakers'][] = $speakerName;
                        }
                    }
                    $sessionData['interventions'][] = $interventionData;
                }

                $sessionsData[] = $sessionData;
            }

            // Générer les résumés pour les sessions
            $jsonDataChunks = array_chunk($sessionsData, 15);
            $sessionsGenerated = [];
            $pattern = '/\{(?:[^{}]|(?R))*\}/';
            foreach ($jsonDataChunks as $chunk) {
                Sleep::for(10)->seconds();
                $jsonData = json_encode(['sessions' => $chunk]);
                [$summaryResponse, $summaryTokenIn, $summaryTokenOut, $summaryTokenTotal] = $this->generateSummaryForSessions($jsonData);
                preg_match_all($pattern, $summaryResponse, $matches);
                foreach ($matches[0] as $match) {
                    $datas = $this->getTextBetweenTags($match, ['title', 'summary', 'meta_description', 'teaser_title', 'teaser_text']);
                    Log::info(json_encode($datas,true));
                    $sessionsGenerated[] = $datas;
                }
            }

            // Intégrer les données générées dans le XML
            $key = 0;
            foreach ($xml->getElementsByTagName('session') as $session) {
                // Créer des éléments CDATA pour chaque donnée générée
                $summaryCDATA = $xml->createCDATASection($sessionsGenerated[$key]['summary'] ?? "");
                $metaDescriptionCDATA = $xml->createCDATASection($sessionsGenerated[$key]['meta_description'] ?? "");
                $teaserTitleCDATA = $xml->createCDATASection($sessionsGenerated[$key]['teaser_title'] ?? "");
                $teaserTextCDATA = $xml->createCDATASection($sessionsGenerated[$key]['teaser_text'] ?? "");

                // Créer des éléments et les ajouter à la session
                $summaryElement = $xml->createElement('introduction');
                $summaryElement->appendChild($summaryCDATA);
                $session->appendChild($summaryElement);

                $metaDescriptionElement = $xml->createElement('meta_description');
                $metaDescriptionElement->appendChild($metaDescriptionCDATA);
                $session->appendChild($metaDescriptionElement);

                $teaserTitleElement = $xml->createElement('teaser_title');
                $teaserTitleElement->appendChild($teaserTitleCDATA);
                $session->appendChild($teaserTitleElement);

                $teaserTextElement = $xml->createElement('teaser_text');
                $teaserTextElement->appendChild($teaserTextCDATA);
                $session->appendChild($teaserTextElement);

                $key++;
            }

            // Sauvegarder le XML modifié
            $xmlContentModified = $xml->saveXML();

            // Écrire le contenu modifié dans un nouveau fichier XML
            $newFileName = 'data4.xml';
            $filePath = public_path() . '/' . $newFileName;
            file_put_contents($filePath, $xmlContentModified);

            return redirect()->back()->with('success', 'Summary saved');
        }
        return redirect()->back()->with('error', 'Something went wrong. Please try again.');
    }

    public function getTextBetweenTags($html, $tags) {
        foreach ($tags as $tag) {
        $pattern = "/<$tag>(.*?)<\/$tag>/";
        preg_match($pattern, $html, $matches);
        $result[$tag] = $matches[1] ?? '';
        }
        return $result;
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

        $summaryTokenIn = $chatResponse->usage->promptTokens ?? 0;
        $summaryTokenOut = $chatResponse->usage->completionTokens ?? 0;
        $summaryTokenTotal = $chatResponse->usage->totalTokens ?? 0;
        $summaryResponse = $chatResponse->choices[0]->message->content;
        return [$summaryResponse, $summaryTokenIn, $summaryTokenOut, $summaryTokenTotal];
    }

    private function generateSummaryForSessions($sessions)
    {
        $prompt = 'PCR Online is a virtual event platform that hosts online conferences and events related to polymerase chain reaction (PCR) and molecular biology techniques.



        You will receive information about sessions from PCR Online events. For each session, you will be provided with the following details:
        
        
        
        - Title
        
        - Subtitle 
        
        - Objectives
        
        - Topics and Specialties
        
        - Interventions with Speakers
        
        
        Your task is to create a very detailed summary, a meta description, a teaser title and text for each session, even if it is duplicate, based on the provided information. The summary should capture the key points, concepts, and insights discussed in the session. You can\'t use double quote in the summary, meta description, teaser title and text.
        
        
        Example Summaries:
        
        
        Title: Stuck between a rock and a hard place: when calcium is the enemy!
        Summary: Have you ever wondered what tools and techniques to use when faced with calcified lesions? Watch this session to discover various cases of severely calcified lesions: the use of IVUS guidance and rota-cut when faced with tough calcium, PCI of a stubborn fibrotic coronary lesion, as well as the Rotascoring technique during a challenging case with an ACS patient.
        Meta-Description: Discover various cases of severely calcified lesions: the use of IVUS guidance and rota-cut when faced with tough calcium, PCI of a stubborn fibrotic coronary lesion, as well as the Rotascoring technique during a challenging case with an ACS patient.
        Teaser Title: Stuck between a rock and a hard place: when calcium is the enemy!
        Teaser Text: Have you ever wondered what tools and techniques to use when faced with calcified lesions? Watch this session to discover various cases of severely calcified lesions: the use of IVUS guidance and rota-cut when faced with tough calcium, PCI of a stubborn fibrotic coronary lesion, as well as the Rotascoring technique during a challenging case with an ACS patient.

        
        Title: Ultra-low contrast techniques in complex and high-risk coronary interventions
        Summary: Explore this session to gain insights into reducing operator reliance on contrast in PCI for enhanced safety and quality, particularly in complex scenarios. Discover essential tips, tricks, and specialized tools tailored for ultra-low-contrast PCI. Additionally, immerse yourself in a practical, step-by-step example through a recorded ultra-low-contrast PCI intervention.
        Meta-Description: Explore this session to gain insights into reducing operator reliance on contrast in PCI for enhanced safety and quality, particularly in complex scenarios. Discover essential tips, tricks, and specialized tools tailored for ultra-low-contrast PCI. Additionally, immerse yourself in a practical, step-by-step example through a recorded ultra-low-contrast PCI intervention.
        Teaser Title: Ultra-low contrast techniques in complex and high-risk coronary interventions
        Teaser Text: Gain insights into reducing operator reliance on contrast in PCI for enhanced safety and quality, particularly in complex scenarios. Discover essential tips, tricks, and specialized tools tailored for ultra-low-contrast PCI.
        

        Title: Lifetime management - Tailoring treatment options to secure future possibilities  
        Summary: Discover key insights in this video session from GulfPCR-GIM 2023 focusing on post-TAVI considerations. Gain understanding into the importance of posterior left ventricular and conduction disorders after TAVI and their impact on long-term outcomes. Explore the latest evidence regarding lifetime management for aortic stenosis patients. Delve into discussions about the impact of TAVI design on durability and TAV-in-TAV options, offering valuable perspectives for optimizing patient care and procedural efficacy.
        Meta-Description: Discover key insights in this GulfPCR-GIM 2023 session focusing on post-TAVI considerations: impact of of posterior left ventricular and conduction disorders after TAVI on long-term outcomes, latest evidence regarding lifetime management for aortic stenosis patients, and impact of TAVI design on durability and TAV-in-TAV options
        Teaser Title: Lifetime management - Tailoring treatment options to secure future possibilities
        Teaser Text: Find out more about the impact of of posterior left ventricular and conduction disorders after TAVI on long-term outcomes, the latest evidence regarding lifetime management for aortic stenosis patients, and the impact of TAVI design on durability and TAV-in-TAV options.

        
        Title: Complex PCI in high bleeding risk patients, the way forward!
        Summary: Consult this session for insights on customizing PCI strategies for high-bleeding-risk patients, and selecting Drug-Eluting Stents in complex PCI. Additionally, examine the outcomes of the Ultimaster DES in high-bleeding-risk trials, particularly in the context of short Dual Antiplatelet Therapy.
        Meta-Description: Consult this session for insights on customizing PCI strategies for high-bleeding-risk patients, and selecting Drug-Eluting Stents (#DES) in complex #PCI. Additionally, examine the outcomes of the Ultimaster DES in high-bleeding-risk trials, particularly in the context of short Dual Antiplatelet Therapy (#DAPT).
        Teaser Title: Complex PCI in high bleeding risk patients, the way forward!
        Teaser Text: Get insights on customizing PCI strategies for high-bleeding-risk patients and selecting DES in complex PCI. Additionally, examine the outcomes of the Ultimaster DES with short DAPT in high-bleeding-risk trials.
        
        After summarizing all the sessions, you need to return the summaries in the following structure:
        
        
        
        [
        
          {
            <id>"Session ID 1"</id>,
        
            <title>"Session Title 1"</title>, 
        
            <summary>"Detailed summary for Session 1..."</summary>,

            <meta_description>"Meta data for Session 1..."</meta_description>,

            <teaser_title>"Teaser Title for Session 1"</teaser_title,

            <teaser_text>"Teaser text for Session 1"</teaser_text>
        
          },
        
          {
            <id>"Session ID 2"</id>,
        
            <title>"Session Title 2"</title>,
        
            <summary>"Detailed summary for Session 2..."</summary>,

            <meta_description>"Meta data for Session 2..."</meta_description>,

            <teaser_title>"Teaser Title for Session 2"</teaser_title>,

            <teaser_text>"Teaser text for Session 2"</teaser_text>
        
          },
        
          ...
        
        ]';

        $message = "Create a very detailed summary, a meta description, a teaser title and text for all the sessions below:\n" . $sessions;
        $resultFromAi = AiFacade::ask($message, $prompt);
        $answerFromAi = $resultFromAi['completion'];
        $metadata = $resultFromAi['metadata'];
        $inputTokens = $resultFromAi['inputTokens'];
        $outputTokens = $resultFromAi['outputTokens'];
        // dd($answerFromAi, $inputTokens, $outputTokens, $metadata);
        
        // $chatResponse = OpenAI::chat()->create([
        //     'model' => 'gpt-3.5-turbo-0125',
        //     'messages' => [['role' => 'user', 'content' => [['type' => 'text', 'text' => $message]]]],
        // ]);

        // $summaryTokenIn = $chatResponse->usage->promptTokens ?? 0;
        // $summaryTokenOut = $chatResponse->usage->completionTokens ?? 0;
        // $summaryTokenTotal = $chatResponse->usage->totalTokens ?? 0;
        // $summaryResponse = $chatResponse->choices[0]->message->content;
        return [$answerFromAi, $inputTokens, $outputTokens, $metadata];
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
        $analyseTokenIn = $visionResponse->usage->promptTokens ?? 0;
        $analyseTokenOut = $visionResponse->usage->completionTokens ?? 0;
        $analyseTokenTotal = $visionResponse->usage->totalTokens ?? 0;
        $analyse = $visionResponse->choices[0]->message->content;
        return [$analyse, $analyseTokenIn, $analyseTokenOut, $analyseTokenTotal];
    }

    private function saveAnalysis($url, $title, $goals, $resources, $summaryResponse, $imageFilename, $analyseResponse, $summaryTokenIn, $summaryTokenOut, $summaryTokenTotal, $analyseTokenIn, $analyseTokenOut, $analyseTokenTotal)
    {
        $analysis = new Analyse();
        $analysis->url = $url;
        $analysis->title = $title;
        $analysis->goals = json_encode($goals);
        $analysis->resources = json_encode($resources);
        $analysis->summary = $summaryResponse;
        $analysis->image = $imageFilename;
        $analysis->image_analyse = $analyseResponse;
        $analysis->summary_tokens_in = $summaryTokenIn;
        $analysis->summary_tokens_out = $summaryTokenOut;
        $analysis->summary_tokens_total = $summaryTokenTotal;
        $analysis->image_analyse_tokens_in = $analyseTokenIn;
        $analysis->image_analyse_tokens_out = $analyseTokenOut;
        $analysis->image_analyse_tokens_total = $analyseTokenTotal;
        $analysis->save();
    }

    private function saveXmlAnalysis($url, $summary)
    {
        $analysis = new XmlSummary();
        $analysis->url = $url;
        $analysis->summary = $summary;
        $analysis->save();
    }

    private function getSpeakerNameById($physicians, $speakerId)
    {
        // Récupérer tous les éléments <physician>
        $physicianElements = $physicians->getElementsByTagName('physician');

        // Parcourir chaque élément <physician>
        foreach ($physicianElements as $physician) {
            // Vérifier si l'attribut id correspond à celui recherché
            if ($physician->getAttribute('id') === $speakerId) {
                // Construire le nom du speaker
                $title = $physician->getElementsByTagName('title')->item(0)->nodeValue ?? '';
                $firstname = $physician->getElementsByTagName('firstname')->item(0)->nodeValue ?? '';
                $lastname = $physician->getElementsByTagName('lastname')->item(0)->nodeValue ?? '';
                return trim("$title $firstname $lastname");
            }
        }
        // Si aucun médecin correspondant n'est trouvé, retourner null
        return null;
    }


    private function getTopicNameById($topicsData, $topicId)
    {
        // Récupérer tous les éléments <topic>
        $topicElements = $topicsData->getElementsByTagName('topic');
    
        // Parcourir chaque élément <topic>
        foreach ($topicElements as $topic) {
            // Vérifier si l'attribut id correspond à celui recherché
            if ($topic->getAttribute('id') === $topicId) {
                // Récupérer le nom du topic
                $nameElement = $topic->getElementsByTagName('name')->item(0);
                if ($nameElement) {
                    return $nameElement->nodeValue;
                }
            }
        }
        // Si aucun topic correspondant n'est trouvé, retourner null
        return null;
    }
    

    // Fonction pour obtenir le nom de la speciality à partir de son ID
    private function getSpecialityNameById($specialitiesData, $specialityId)
    {
        // Récupérer tous les éléments <speciality>
        $specialityElements = $specialitiesData->getElementsByTagName('speciality');
    
        // Parcourir chaque élément <speciality>
        foreach ($specialityElements as $speciality) {
            // Vérifier si l'attribut id correspond à celui recherché
            if ($speciality->getAttribute('id') === $specialityId) {
                // Récupérer le nom de la spécialité
                return $speciality->getElementsByTagName('name')->item(0)->nodeValue;
            }
        }
        // Si aucune spécialité correspondante n'est trouvée, retourner null
        return null;
    }
    
}
