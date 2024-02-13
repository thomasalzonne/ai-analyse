<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Models\EmailCampaignOpen;
use App\Models\EmailCampaignClick; 
use App\Models\EmailList;
use App\Models\EmailSubscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;

class EmailCampaignsActionsImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-campaigns-actions-import  {--email-campaign-id=} {--email-client-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve actions from email campaigns and store them in the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('email-campaign-id')) {
            $emailCampaigns = EmailCampaign::where('id', $this->option('email-campaign-id'))->get();
        } elseif ($this->option('email-client-id')) {
            $emailCampaigns = EmailCampaign::where('email_client_id', $this->option('email-client-id'))->get();
        } else {
            $emailCampaigns = EmailCampaign::all();
        }

        foreach ($emailCampaigns as $emailCampaign) {
            $emailClient = $emailCampaign->emailClient;
            $service = new \App\Services\CampaignMonitorService($emailClient);

            $latestOpenDate = $emailCampaign->opens()?->max('date');
            if ($latestOpenDate) {
                $this->info('Fetching opens since ' . $latestOpenDate . ' for ' . $emailClient->name . ' :: ' . $emailCampaign->name);
                $opens = $service->fetchEmailCampaignOpens($emailCampaign->remote_id, $latestOpenDate);
            } else {
                $this->info('Fetching opens for ' . $emailClient->name . ' :: ' . $emailCampaign->name);
                $opens = $service->fetchEmailCampaignOpens($emailCampaign->remote_id);
            }   

            $this->info('Fetched ' . count($opens) . ' opens for ' . $emailClient->name . ' :: ' . $emailCampaign->name);
            $this->info('Saving opens for ' . $emailClient->name . ' :: ' . $emailCampaign->name);
            $progressBar = $this->output->createProgressBar(count($opens));

            foreach ($opens as $open) {
                $list = EmailList::where('remote_id', $open['list_id'])->first();
                EmailCampaignOpen::create([
                    'email_address' => $open['email_address'],
                    'email_campaign_id' => $emailCampaign->id,
                    'email_list_id' => $list->id,
                    'date' => $open['date'],
                    'latitude' => $open['latitude'],
                    'longitude' => $open['longitude'],
                    'city' => $open['city'],
                    'region' => $open['region'],
                    'country_code' => $open['country_code'],
                    'country_name' => $open['country_name'],
                ]);

                $progressBar->advance();
            }
            $progressBar->finish();
            $this->line('');
            $this->info('Opens import finished for ' . $emailClient->name . ' :: ' . $emailCampaign->name);

            $latestClickDate = $emailCampaign->clicks()?->max('date');
            if ($latestClickDate) {
                $this->info('Fetching clicks since ' . $latestClickDate . ' for ' . $emailClient->name . ' :: ' . $emailCampaign->name);
                $clicks = $service->fetchEmailCampaignClicks($emailCampaign->remote_id, $latestClickDate);
            } else {
                $this->info('Fetching clicks for ' . $emailClient->name . ' :: ' . $emailCampaign->name);
                $clicks = $service->fetchEmailCampaignClicks($emailCampaign->remote_id);
            }   

            $this->info('Fetched ' . count($clicks) . ' clicks for ' . $emailClient->name . ' :: ' . $emailCampaign->name);
            $this->info('Saving clicks for ' . $emailClient->name . ' :: ' . $emailCampaign->name);
            $progressBar = $this->output->createProgressBar(count($clicks));

            foreach ($clicks as $click) {
                $list = EmailList::where('remote_id', $click['list_id'])->first();
                EmailCampaignClick::create([
                    'email_address' => $click['email_address'],
                    'email_campaign_id' => $emailCampaign->id,
                    'email_list_id' => $list->id,
                    'date' => $click['date'],
                    'latitude' => $click['latitude'],
                    'longitude' => $click['longitude'],
                    'city' => $click['city'],
                    'region' => $click['region'],
                    'country_code' => $click['country_code'],
                    'country_name' => $click['country_name'],
                ]);

                $progressBar->advance();
            }
            $progressBar->finish();
            $this->line('');
            $this->info('Clicks import finished for ' . $emailClient->name . ' :: ' . $emailCampaign->name);
        }
    }
}
