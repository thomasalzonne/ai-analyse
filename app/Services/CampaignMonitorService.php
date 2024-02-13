<?php

namespace App\Services;

use App\Models\EmailClient;

class CampaignMonitorService
{
    protected $api_key;
    
    protected $client_id;

    public function __construct(EmailClient $emailClient)
    {
        $this->api_key = $emailClient->api_key;
        $this->client_id = $emailClient->remote_id;
    }

    public function clients($clientId = null)
    {
        if ($clientId == null){
            $clientId = $this->client_id;
        }
        
        return new \CS_REST_Clients($clientId, $this->getAuthTokens());
    }
    
    public function campaigns($campaignId = null)
    {
        return new \CS_REST_Campaigns($campaignId, $this->getAuthTokens());
    }

    public function lists($listId = null)
    {
        return new \CS_REST_Lists($listId, $this->getAuthTokens());
    }

    public function segments($segmentId = null)
    {
        return new \CS_REST_Segments($segmentId, $this->getAuthTokens());
    }

    public function subscribers($listId = null)
    {
        return new \CS_REST_Subscribers($listId, $this->getAuthTokens());
    }

    protected function getAuthTokens()
    {
        if ($this->api_key != null){
            return [
                'api_key' => $this->api_key
            ];
        }

        return [
            'api_key' => config('services.campaignmonitor.api_key')
        ];
    }

    public function fetchSentCampaigns($sent_from_date = null, $sent_to_date = null, $tags = null, $limit = null, $order_direction = 'DESC')
    {
        $campaigns = [];
        $results = [];
        $page_number = 1;
        $page_size = 1000;
        $limit = $limit ?? 10000;
        
        do {
            $result = $this->clients()->get_campaigns($tags, $page_number, $page_size, $order_direction, $sent_from_date, $sent_to_date);
            $response = $result->response;
            $results = array_merge($results, $response->Results);
            $page_number++;
        } while ($response->PageNumber < $response->NumberOfPages && $page_number*$page_size < $limit);

        foreach ($results as $result) {
            $campaigns[] = [
                'remote_id' => $result->CampaignID,
                'name' => $result->Name,
                'from_name' => $result->FromName,
                'from_email' => $result->FromEmail,
                'reply_to' => $result->ReplyTo,
                'subject' => $result->Subject,
                'sent_date' => $result->SentDate,
                'tags' => $result->Tags,
                'recipients' => $result->TotalRecipients,
                'webversion_url' => $result->WebVersionURL,
            ];
        }
        
        return $campaigns;
    }

    public function fetchCampaignSummary($campaignId)
    {
        $result = $this->campaigns($campaignId)->get_summary();
        $campaign = $result->response;

        $campaignDetails = [
            'name' => $campaign->Name,
            'recipients' => $campaign->Recipients,
            'total_opened' => $campaign->TotalOpened,
            'clicks' => $campaign->Clicks,
            'unsubscribed' => $campaign->Unsubscribed,
            'bounced' => $campaign->Bounced,
            'unique_opened' => $campaign->UniqueOpened,
            'spam_complaints' => $campaign->SpamComplaints,
            'webversion_url' => $campaign->WebVersionURL,
            'worldview_url' => $campaign->WorldviewURL,
        ];

        return $campaignDetails;
    }

    public function fetchClientLists()
    {
        $lists = [];
        $result = $this->clients()->get_lists();
        foreach ($result->response as $list) {
            $lists[] = [
                'remote_id' => $list->ListID,
                'name' => $list->Name,
            ];
        }

        return $lists;
    }

    public function fetchListStats($listId)
    {
        $result = $this->lists($listId)->get_stats();
        $list = $result->response;
        $listStats['active_subscribers'] = $list->TotalActiveSubscribers??0;
        $listStats['unsubscribes'] = $list->TotalUnsubscribes??0;
        $listStats['deleted'] = $list->TotalDeleted??0;
        $listStats['bounces'] = $list->TotalBounces??0;
        

        return $listStats;
    }

    public function fetchListSubscribers($listId, $limit = null, $added_since = '', $page_number = NULL, $page_size = NULL, $order_field = NULL, $order_direction = NULL, $include_tracking_pref = NULL)
    {
        $subscribers = [];
        $results = [];
        $page_number = 1;
        $page_size = 1000;
        $limit = $limit ?? 250000;

        do {
            $result = $this->lists($listId)->get_active_subscribers($added_since, $page_number, $page_size, $order_field, $order_direction, $include_tracking_pref);
            $response = $result->response;
            $results = array_merge($results, $response->Results);
            $page_number++;
        } while ($response->PageNumber < $response->NumberOfPages && $page_number*$page_size < $limit);

        foreach ($results as $result) {
            $subscribers[] = [
                'email_address' => $result->EmailAddress,
                'name' => $result->Name,
                'joined_at' => $result->ListJoinedDate,
                'reads_email_with' => $result->ReadsEmailWith,
                'custom_fields' => $result->CustomFields,
            ];
        }

        return $subscribers;
    }

    public function fetchEmailCampaignOpens($campaignId, $since = '', $page_number = NULL, $page_size = NULL, $order_field = 'date', $order_direction = 'asc')
    {
        $opens = [];
        $results = [];
        $page_number = 1;
        $page_size = 1000;
        $limit = $limit ?? 100000;

        do {
            $result = $this->campaigns($campaignId)->get_opens($since, $page_number, $page_size, $order_field, $order_direction);
            $response = $result->response;
            $results = array_merge($results, $response->Results);
            $page_number++;
        } while ($response->PageNumber < $response->NumberOfPages && $page_number*$page_size < $limit);

        foreach ($results as $result) {
            $opens[] = [
                'email_address' => $result->EmailAddress,
                'list_id' => $result->ListID,
                'date' => $result->Date,
                'latitude' => $result->Latitude??null,
                'longitude' => $result->Longitude??null,
                'city' => $result->City??null,
                'region' => $result->Region??null,
                'country_code' => $result->CountryCode??null,
                'country_name' => $result->CountryName??null,
            ];
        }

        return $opens;
    }

    
    public function fetchEmailCampaignClicks($campaignId, $since = '', $page_number = NULL, $page_size = NULL, $order_field = 'date', $order_direction = 'asc')
    {
        $opens = [];
        $results = [];
        $page_number = 1;
        $page_size = 1000;
        $limit = $limit ?? 100000;

        do {
            $result = $this->campaigns($campaignId)->get_clicks($since, $page_number, $page_size, $order_field, $order_direction);
            $response = $result->response;
            $results = array_merge($results, $response->Results);
            $page_number++;
        } while ($response->PageNumber < $response->NumberOfPages && $page_number*$page_size < $limit);

        foreach ($results as $result) {
            $clicks[] = [
                'email_address' => $result->EmailAddress,
                'list_id' => $result->ListID,
                'date' => $result->Date,
                'latitude' => $result->Latitude??null,
                'longitude' => $result->Longitude??null,
                'city' => $result->City??null,
                'region' => $result->Region??null,
                'country_code' => $result->CountryCode??null,
                'country_name' => $result->CountryName??null,
            ];
        }

        return $clicks;
    }

}