<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DOMDocument;
use App\Facades\AiFacade;
use App\Services\AiService;
use Illuminate\Support\Sleep;

class XmlProgramme extends Model
{
    protected $fillable = [
        'url',
        'summary',
    ];

    public function generateSummary($xmlData, $name = 'data')
    {
        // Parse the XML data
        $xml = new DOMDocument();
        $xml->loadXML($xmlData);
    
        // Initialize sessions generated array
        $sessionsGenerated = [];
    
        // Extract relevant information from the XML
        $sessions = [];
        $sessionsElements = $xml->getElementsByTagName('session');
        foreach ($sessionsElements as $sessionElement) {
            $session = [
                'title' => $sessionElement->getElementsByTagName('title')->item(0)->nodeValue,
                'subtitle' => $sessionElement->getElementsByTagName('subtitle')->item(0)->nodeValue ?? '',
                'objectives' => $sessionElement->getElementsByTagName('objectives')->item(0) ? $this->extractObjectives($sessionElement->getElementsByTagName('objectives')->item(0)) : [],
                'topics' => $sessionElement->getElementsByTagName('topics')->item(0) ? $this->extractTopics($sessionElement->getElementsByTagName('topics')->item(0)) : [],
                'interventions' => $sessionElement->getElementsByTagName('interventions')->item(0) ? $this->extractInterventions($sessionElement->getElementsByTagName('interventions')->item(0)) : [],
            ];
    
            $sessions[] = $session;
        }
    
        // Generate summaries for sessions in chunks
        $chunkSize = 5;
        $sessionChunks = array_chunk($sessions, $chunkSize);
        foreach ($sessionChunks as $sessionChunk) {
            $jsonData = json_encode(['sessions' => $sessionChunk]);
            Sleep::for(10)->seconds();
            [$summaryResponse, , ,] = $this->generateSummaryForSessions($jsonData);
    
            // Extract generated session data
            $pattern = '/\{(?:[^{}]|(?R))*\}/';
            preg_match_all($pattern, $summaryResponse, $matches);
            foreach ($matches[0] as $match) {
                $datas = $this->getTextBetweenTags($match, ['title', 'summary', 'meta_description', 'teaser_title', 'teaser_text']);
                $sessionsGenerated[] = $datas;
            }
        }
    
        // Integrate generated data into the XML
        $key = 0;
        foreach ($xml->getElementsByTagName('session') as $session) {
            $summaryCDATA = $xml->createCDATASection($sessionsGenerated[$key]['summary'] ?? "");
            $metaDescriptionCDATA = $xml->createCDATASection($sessionsGenerated[$key]['meta_description'] ?? "");
            $teaserTitleCDATA = $xml->createCDATASection($sessionsGenerated[$key]['teaser_title'] ?? "");
            $teaserTextCDATA = $xml->createCDATASection($sessionsGenerated[$key]['teaser_text'] ?? "");
    
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
    
        // Save the modified XML content
        $xmlContentModified = $xml->saveXML();
    
        // Write the modified content to a new XML file
        $newFileName = $name . '.xml';
        $filePath = public_path() . '/' . $newFileName;
        file_put_contents($filePath, $xmlContentModified);
    
        return redirect()->back()->with('success', 'Summary saved');
    }
    

    private function extractObjectives($objectivesElement)
    {
        $objectives = [];
        foreach ($objectivesElement->getElementsByTagName('objective') as $objectiveElement) {
            $objectives[] = $objectiveElement->nodeValue;
        }
        return $objectives;
    }

    private function extractTopics($topicsElement)
    {
        $topics = [];
        foreach ($topicsElement->getElementsByTagName('topic') as $topicElement) {
            $topic = [
                'name' => $topicElement->getElementsByTagName('name')->item(0)->nodeValue,
            ];

            $specialityElement = $topicElement->getElementsByTagName('speciality')->item(0);
            if ($specialityElement) {
                $topic['speciality'] = $specialityElement->nodeValue;
            }

            $topics[] = $topic;
        }
        return $topics;
    }

    private function extractInterventions($interventionsElement)
    {
        $interventions = [];
        foreach ($interventionsElement->getElementsByTagName('intervention') as $interventionElement) {
            $intervention = [
                'title' => $interventionElement->getElementsByTagName('title')->item(0)->nodeValue,
            ];

            $interventions[] = $intervention;
        }
        return $interventions;
    }

    private function generateSummaryForSessions($sessions)
    {
        $prompt = 'PCR is an organisation dedicated to education and information in the field of cardiovascular therapies, most notably for cardiovascular intervention and interventional medicine. Its activities cover a large spectrum, from the organisation of annual Courses in Europe, Asia, the Middle East and Africa to editing a scientific journal, publishing textbooks as well as providing training seminars on thematic subjects.



        You will receive information about sessions from PCR Online events. For each session, you will be provided with the following details:
        
        
        
        - Title
        
        - Subtitle 
        
        - Objectives
        
        - Topics and Specialties
        
        - Interventions with Speakers
        
        
        Your task is to create a very detailed summary, a meta description, a teaser title and text for each session, even if it is duplicate, based on the provided information. The summary should capture the key points, concepts, and insights discussed in the session. You can\'t use double quote in the summary, meta description, teaser title and teaser text.
        
        
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
            <id>Session ID 1</id>,
        
            <title>Session Title 1</title>, 
        
            <summary>Detailed summary for Session 1...</summary>,

            <meta_description>Meta data for Session 1...</meta_description>,

            <teaser_title>Teaser Title for Session 1</teaser_title,

            <teaser_text>Teaser text for Session 1</teaser_text>
        
          },
        
          {
            <id>Session ID 2</id>,
        
            <title>Session Title 2</title>,
        
            <summary>Detailed summary for Session 2...</summary>,

            <meta_description>Meta data for Session 2...</meta_description>,

            <teaser_title>Teaser Title for Session 2</teaser_title>,

            <teaser_text>Teaser text for Session 2</teaser_text>
        
          },
        
          ...
        
        ]';

        $message = "Create a very detailed summary, a meta description, a teaser title and text for all the sessions below:\n" . $sessions;
        $resultFromAi = AiFacade::ask($message, $prompt);
        $answerFromAi = $resultFromAi['completion'];
        $metadata = $resultFromAi['metadata'];
        $inputTokens = $resultFromAi['inputTokens'];
        $outputTokens = $resultFromAi['outputTokens'];

        return [$answerFromAi, $inputTokens, $outputTokens, $metadata];
    }

    public function getTextBetweenTags($html, $tags) {
        foreach ($tags as $tag) {
        $pattern = "/<$tag>(.*?)<\/$tag>/";
        preg_match($pattern, $html, $matches);
        $result[$tag] = $matches[1] ?? '';
        }
        return $result;
    }
}