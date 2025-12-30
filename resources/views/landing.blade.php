<x-guest-layout>
    <div class="landing-container">
        <div class="landing-content">
            <img class="logo landing-logo" src="/logo.png" alt="Happy Notes">

            <h1 class="landing-title">{{ config('app.name') }}</h1>

            <p class="landing-tagline">
                A note-taking app for <span class="landing-strikethrough">chaotic</span> free-spirited minds
            </p>

            <p class="landing-emojis">💥🧠📝🎨🚀</p>

            <p class="landing-description">
                Organize your notes with emojis. No folders, no tags.
            </p>

            <div class="landing-buttons">
                <a href="{{ route('login') }}" class="btn btn-primary">
                    Log in
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-secondary">
                        Sign up
                    </a>
                @endif
            </div>

            <div class="landing-footer">
                <p class="landing-opensource">Free & open source.</p>
                <a href="https://github.com/rickvanderwolk/happynotes" target="_blank" rel="noopener noreferrer" class="landing-github">
                    View source on GitHub
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
