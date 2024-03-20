<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Analyse') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('analyse') }}" method="post">
                    @csrf
                    <input type="text" name="url" placeholder="URL">
                    <select name="type">
                        <option value="page">Page classique</option>
                        <option value="xml">Lien XML</option>
                    </select>
                    <button type="submit">Analyser</button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>