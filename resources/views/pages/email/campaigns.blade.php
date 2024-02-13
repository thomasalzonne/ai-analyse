<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Email campaigns') }} // {{ $domain->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <ul>
                        @foreach ($campaigns as $campaign)
                            <li>
                                <a href="{{ route('email.campaign', ['domain' => $domain, 'emailCampaign' => $campaign]) }}" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-600">
                                    {{ $campaign->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>


                </div>
            </div>
        </div>
    </div>

    <script>
      const search = instantsearch({
        indexName: "email_campaigns_index",
        searchClient: instantMeiliSearch(url, apiKey, {
            placeholderSearch: true,
            primaryKey: "id",
            matchingStrategy: "all",
            finitePagination: true,
        }),
        routing: {
            stateMapping: {
                stateToRoute(uiState) {
                    const indexUiState = uiState["email_campaigns_index"];
                    return {
                        q: indexUiState.query,
                    };
                },
                routeToState(routeState) {
                    return {
                        ["email_campaigns_index"]: {
                            query: routeState.hasOwnProperty("instant_search")
                                ? routeState.instant_search.query
                                : routeState.q,
                            refinementList: {
                                email_client: 'PCR',
                            },
                        },
                    };
                },
            },
        },
    });
    </script>
</x-app-layout>
