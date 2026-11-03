<x-app-layout>
    <x-slot name="title">About</x-slot>
    <div class="container mx-auto">
        <div class="menu-header">
            <a href="{{ route('notes.show') }}" aria-label="Back to notes">
                <img src="/logo.png" alt="" class="logo logo-menu">
            </a>
            <h1 class="section-title">HappyNotes</h1>
        </div>

        <div class="about-intro">
            <p class="about-tagline">
                A note-taking app for <span class="about-strikethrough">chaotic</span> free-spirited minds
            </p>
            <p class="about-promise">No folders, no tags, just emojis ✨</p>
        </div>

        <section class="about-section">
            <h2 class="about-heading">Your notes are yours</h2>
            <p>No ads, no tracking. You can export your notes anytime under Account.</p>
            @if (config('app.official_instance'))
                <p>happynotes.cc is the free hosted version of HappyNotes, hosted in the Netherlands (EU). HappyNotes itself is open source, so you can also run your own copy: <a href="https://github.com/rickvanderwolk/happynotes" target="_blank" rel="noopener noreferrer" data-no-navigate class="text-link">see the code on GitHub</a>.</p>
            @else
                <p>This is a self-hosted copy of HappyNotes, which is open source: <a href="https://github.com/rickvanderwolk/happynotes" target="_blank" rel="noopener noreferrer" data-no-navigate class="text-link">see the code on GitHub</a>.</p>
            @endif
        </section>

        <section class="about-section">
            <h2 class="about-heading">Say hi</h2>
            <p>Enjoying HappyNotes, or have an idea? Drop a note at <a href="mailto:hi@happynotes.cc" data-no-navigate class="text-link">hi@happynotes.cc</a>.</p>
            <p>Found a security issue? Email the same address instead of opening a public issue.</p>
        </section>

        <div class="app-version-text">{{ config('app.version') }}</div>
    </div>
</x-app-layout>
