<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

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
        <meta name="app-base-url" content="{{ url('/') }}">
        <meta name="app-current-route-name" content="{{ Route::currentRouteName() }}">

        <div id="app" class="container custom-scrollbar">
            <div class="row">
                <div class="col-12 col-sm-11 col-md-10 col-lg-9 col-xl-6 ms-auto me-auto">
                    {{-- Simple navbar for account pages --}}
                    <div id="main-navbar" class="main-navbar">
                        <div class="navbar-inner">
                            <div class="row g-0">
                                <div class="col-12">
                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-between">
                                            <h3 class="emoji-wrapper"></h3>
                                            <h3 class="emoji-wrapper">
                                                <a href="{{ route('notes.show') }}" aria-label="Close">
                                                    <i class="fa fa-close"></i>
                                                </a>
                                            </h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
