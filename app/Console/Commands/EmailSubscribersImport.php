<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Helper\ProgressBar;

class EmailSubscribersImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-subscribers-import  {--email-list-id=} {--email-client-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retrieve subscribers from email clients and store them in the database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('email-list-id')) {
            $emailLists = \App\Models\EmailList::where('id', $this->option('email-list-id'))->get();
        } elseif ($this->option('email-client-id')) {
            $emailLists = \App\Models\EmailList::where('email_client_id', $this->option('email-client-id'))->get();
        } else {
            $emailLists = \App\Models\EmailList::all();
        }

        foreach ($emailLists as $emailList) {
            $emailClient = $emailList->emailClient;
            $service = new \App\Services\CampaignMonitorService($emailClient);

            $this->info('Fetching subscribers for ' . $emailList->name);
            $subscribers = $service->fetchListSubscribers($emailList->remote_id);
            $this->info('Fetched ' . count($subscribers) . ' subscribers for ' . $emailClient->name . ' :: ' . $emailList->name);
            $this->info('Saving subscribers for ' . $emailClient->name . ' :: ' . $emailList->name);
            $progressBar = $this->output->createProgressBar(count($subscribers));

            foreach ($subscribers as $subscriber) {
                \App\Models\EmailSubscriber::updateOrCreate([
                    'email_address' => $subscriber['email_address'],
                    'email_list_id' => $emailList->id,
                ], [
                    'name' => $subscriber['name'],
                    'joined_at' => $subscriber['joined_at'],
                    'custom_fields' => $subscriber['custom_fields'],
                    'reads_email_with' => $subscriber['reads_email_with'],
                ]);

                $progressBar->advance();
            }
            $progressBar->finish();
            $this->line('');
            $this->info('Subscribers import finished for ' . $emailClient->name . ' :: ' . $emailList->name);
        }
    }
}
