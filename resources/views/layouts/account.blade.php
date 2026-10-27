<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#241B0F" media="(prefers-color-scheme: dark)">
        <meta name="theme-color" content="#FFFAF2" media="(prefers-color-scheme: light)">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' – ' : '' }}{{ config('app.name', 'happynotes') }}</title>
        <meta name="description" content="💥🧠📝🎨🚀">

        <link rel="icon" type="image/png" href="/favicon-96x96.png?v=2" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2" />
        <link rel="shortcut icon" href="/favicon.ico?v=2" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=2" />
        <meta name="apple-mobile-web-app-title" content="HappyNotes" />
        <link rel="manifest" href="/site.webmanifest" />

        <!-- Styles -->
        @vite(['resources/css/app.scss'])
        @livewireStyles
    </head>
    <body>
        <meta name="app-base-url" content="{{ url('/') }}">
        <meta name="app-current-route-name" content="{{ Route::currentRouteName() }}">
        <meta name="app-route-notes-show" content="{{ route('notes.show') }}">

        <div id="app" class="container custom-scrollbar">
            <div class="row">
                <div class="col-12 app-column">
                    {{-- Simple navbar for account pages --}}
                    <nav id="main-navbar" class="main-navbar" aria-label="Main">
                        {{-- Like the other pages: the logo above the rail on larger screens --}}
                        <a href="{{ route('notes.show') }}" class="rail-logo" aria-label="Back to notes">
                            <img src="/logo.png" alt="">
                        </a>
                        <div class="navbar-inner">
                            <div class="row g-0">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-between">
                                            <div class="emoji-wrapper quick-actions">
                                                <a href="{{ route('notes.show') }}" class="quick-new" aria-label="Close">
                                                    <x-icon name="close" />
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </nav>

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
