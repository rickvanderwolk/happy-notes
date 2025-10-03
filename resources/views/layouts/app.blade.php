<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#000000" media="(prefers-color-scheme: dark)">
        <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">

        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="turbolinks-cache-control" content="no-preview">

        <title>{{ config('app.name', 'happynotes') }}</title>
        <meta name="description" content="💥🧠📝🎨🚀">

        <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
        <meta name="apple-mobile-web-app-title" content="happynotes" />
        <link rel="manifest" href="/site.webmanifest" />

        <!-- Styles -->
        @vite(['resources/css/app.scss'])
        @livewireStyles
    </head>
    <body>
        <x-banner/>

        <meta name="app-base-url" content="{{ url('/') }}">
        <meta name="app-current-route-name" content="{{ Route::currentRouteName() }}">
        <meta name="app-note-uuid" content="{{ optional(request()->route('note'))->uuid }}">
        <meta name="app-route-dashboard" content="{{ route('dashboard') }}">
        <meta name="app-route-filter-show" content="{{ route('filter.show') }}">
        <meta name="app-route-filter-exclude-show" content="{{ route('filter.exclude.show') }}">
        <meta name="app-route-filter-search-show" content="{{ route('filter.search.show') }}">
        <meta name="app-route-menu-show" content="{{ route('menu.show') }}">
        <meta name="app-route-notes-show" content="{{ route('notes.show') }}">
        <meta name="app-route-note-create" content="{{ route('note.create') }}">
        <meta name="app-route-note-show" content="{{ route('note.show', ':note') }}">
        <meta name="app-route-note-title-show" content="{{ route('note.title.show', ':note') }}">
        <meta name="app-route-note-emojis-show" content="{{ route('note.emojis.show', ':note') }}">
        <meta name="app-route-profile-show" content="{{ route('profile.show',) }}">

        <div id="app" class="container custom-scrollbar">
            <div class="row">
                <div class="col-12 col-sm-11 col-md-10 col-lg-9 col-xl-6 ms-auto me-auto">
                    <livewire:navbar />

                    <main>
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>

        <!-- Scripts -->
        @vite(['resources/js/app.js'])
        @livewireScripts
    </body>
</html>
