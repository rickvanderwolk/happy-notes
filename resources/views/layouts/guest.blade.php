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
        <meta name="description" content="A note-taking app for free-spirited minds. No folders, no tags, just emojis.">
        {{-- Only the landing page of the official instance is meant to be found; self-hosted copies and the auth pages stay out of search results. --}}
        @if (request()->routeIs('home') && config('app.official_instance'))
            <link rel="canonical" href="{{ url('/') }}">
            <meta property="og:type" content="website">
            <meta property="og:site_name" content="HappyNotes">
            <meta property="og:title" content="HappyNotes">
            <meta property="og:description" content="A note-taking app for free-spirited minds. No folders, no tags, just emojis.">
            <meta property="og:url" content="{{ url('/') }}">
            <meta property="og:image" content="{{ asset('logo.png') }}">
            <meta name="twitter:card" content="summary">
            @php
                $structuredData = [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebApplication',
                    'name' => 'HappyNotes',
                    'url' => url('/'),
                    'description' => 'A note-taking app for free-spirited minds. No folders, no tags, just emojis.',
                    'applicationCategory' => 'ProductivityApplication',
                    'operatingSystem' => 'Web',
                    'image' => asset('logo.png'),
                    'sameAs' => ['https://github.com/rickvanderwolk/happy-notes'],
                ];
            @endphp
            <script type="application/ld+json" nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
        @else
            <meta name="robots" content="noindex, nofollow">
        @endif

        <link rel="icon" type="image/png" href="/favicon-96x96.png?v=2" sizes="96x96" />
        <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2" />
        <link rel="shortcut icon" href="/favicon.ico?v=2" />
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png?v=2" />
        <meta name="apple-mobile-web-app-title" content="HappyNotes" />
        <link rel="manifest" href="/site.webmanifest" />

        <!-- Styles -->
        @vite(['resources/css/guest.scss'])
        @livewireStyles
    </head>
    <body>
        <main class="guest-main">
            {{ $slot }}
        </main>

        <!-- Scripts -->
        @livewireScripts
    </body>
</html>
