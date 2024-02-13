<?php

namespace App\Console\Commands;

use App\Models\EmailCampaign;
use App\Models\EmailClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;

class EmailCampaignsImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-campaigns-import {--email-client-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve sent campaigns from email clients and store them in the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('email-client-id')) {
            $emailClients = EmailClient::where('id', $this->option('email-client-id'))->get();
        } else {
            $emailClients = EmailClient::all();
        }

        foreach ($emailClients as $emailClient) {
            $service = new \App\Services\CampaignMonitorService($emailClient);
            $this->info('Fetching campaigns for ' . $emailClient->name);
            $campaigns = $service->fetchSentCampaigns();
            $this->info('Fetched ' . count($campaigns) . ' campaigns for ' . $emailClient->name);
            $this->info('Saving campaigns for ' . $emailClient->name);
            $progressBar = $this->output->createProgressBar(count($campaigns));

            foreach ($campaigns as $campaign) {
                $campaign = EmailCampaign::updateOrCreate([
                    'remote_id' => $campaign['remote_id'],
                    'email_client_id' => $emailClient->id,
                ], [
                    'name' => $campaign['name'],
                    'from_name' => $campaign['from_name'],
                    'from_email' => $campaign['from_email'],
                    'reply_to' => $campaign['reply_to'],
                    'subject' => $campaign['subject'],
                    'sent_date' => $campaign['sent_date'],
                    'tags' => $campaign['tags'],
                    'recipients' => $campaign['recipients'],
                    'webversion_url' => $campaign['webversion_url'],
                ]);

                if ($campaign->total_opened == null || $campaign->sent_date < now()->subMonth()) {
                    $campaignSummary = $service->fetchCampaignSummary($campaign->remote_id);
                    $campaign->update([
                        'total_opened' => $campaignSummary['total_opened'],
                        'unique_opened' => $campaignSummary['unique_opened'],
                        'clicks' => $campaignSummary['clicks'],
                        'unsubscribed' => $campaignSummary['unsubscribed'],
                        'bounced' => $campaignSummary['bounced'],
                        'spam_complaints' => $campaignSummary['spam_complaints'],
                        'worldview_url' => $campaignSummary['worldview_url'],
                    ]);
                }

                $campaign->screenshot('desktop');
                $campaign->screenshot('mobile');

                $progressBar->advance();
            }
            $progressBar->finish();
            $this->line('');
            $this->info('Campaigns import finished for ' . $emailClient->name);
        }
    }
}
