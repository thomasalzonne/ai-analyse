<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Email campaigns') }} // {{ $emailCampaign->emailClient->domain->name }} // {{ $emailCampaign->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <h1>{{ $emailCampaign->subject }}</h1>
                    <h2>{{ $emailCampaign->name }}</h2>

                    <div>
                        <a href="{{ $emailCampaign->webversion_url }}" target="_blank" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-600">
                            View webversion
                        </a>

                    </div>

                    <div>
                      <p>
                        Total recipients: {{ $emailCampaign->recipients }}
                      </p>
                      <p>
                        Total opened: {{ $emailCampaign->total_opened }}
                      </p>
                      <p>
                        Unique opened: {{ $emailCampaign->unique_opened }}
                      </p>
                      <p>
                        Total clicks: {{ $emailCampaign->clicks }}
                      </p>
                      <p>
                        Unsubscribes: {{ $emailCampaign->unsubscribed }}
                      </p>
                      <p>
                        Bounced: {{ $emailCampaign->bounced }}
                      </p>
                      <p>
                        Spam complaints: {{ $emailCampaign->spam_complaints }}
                      </p>
                    </div>

                    <div>
                      <img src="{{ route('email.campaign.screenshot', ['domain' => $emailCampaign->emailClient->domain, 'emailCampaign' => $emailCampaign, 'device' => 'desktop']) }}" alt="Desktop screenshot" />
                      <img src="{{ route('email.campaign.screenshot', ['domain' => $emailCampaign->emailClient->domain, 'emailCampaign' => $emailCampaign, 'device' => 'mobile']) }}" alt="Mobile screenshot" />
                    </div>


                </div>
            </div>
        </div>
    </div>
</x-app-layout>
