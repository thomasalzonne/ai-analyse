<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;

class EmailListsImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-lists-import {--email-client-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve lists from email clients and store them in the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('email-client-id')) {
            $emailClients = \App\Models\EmailClient::where('id', $this->option('email-client-id'))->get();
        } else {
            $emailClients = \App\Models\EmailClient::all();
        }

        foreach ($emailClients as $emailClient) {
            $service = new \App\Services\CampaignMonitorService($emailClient);
            $this->info('Fetching lists for ' . $emailClient->name);
            $lists = $service->fetchClientLists();
            $this->info('Fetched ' . count($lists) . ' lists for ' . $emailClient->name);
            $this->info('Saving lists for ' . $emailClient->name);
            $progressBar = $this->output->createProgressBar(count($lists));

            foreach ($lists as $list) {
                $listStats = $service->fetchListStats($list['remote_id']);
                \App\Models\EmailList::updateOrCreate([
                    'remote_id' => $list['remote_id'],
                    'email_client_id' => $emailClient->id,
                ], [
                    'name' => $list['name'],
                    'active_subscribers' => $listStats['active_subscribers'],
                ]);

                $progressBar->advance();
            }
            $progressBar->finish();
            $this->line('');
            $this->info('Lists import finished for ' . $emailClient->name);
        }
    }
}
