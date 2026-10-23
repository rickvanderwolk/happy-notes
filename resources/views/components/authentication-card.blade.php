<div class="auth-page">
    <div class="auth-card">
        <div class="auth-card-header">
            {{ $logo }}
        </div>

        {{ $slot }}
    </div>

    <div class="version-text auth-version">
        {{ config('app.name') }} {{ config('app.version') }}
        <span class="auth-version-separator">•</span>
        <a href="https://github.com/rickvanderwolk/happynotes" target="_blank" rel="noopener noreferrer">
            GitHub
        </a>
    </div>
</div>
