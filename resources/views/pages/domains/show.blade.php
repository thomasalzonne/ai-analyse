<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Domains') }} // {{ $domain->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <ul>
                      <li>
                        <a href="{{ route('email.campaigns', ['domain' => $domain]) }}" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-600">
                        View campaigns
                    </a>
                      </li>
                      <li>
                        <a href="{{ route('email.lists', ['domain' => $domain]) }}" class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-600">
                        View lists
                    </a>
                      </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
