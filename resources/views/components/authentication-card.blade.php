<div class="min-h-screen flex flex-col justify-center items-center p-4">
    <div class="auth-card">
        <div class="text-center mb-6">
            {{ $logo }}
        </div>

        {{ $slot }}
    </div>

    <div class="version-text text-center mt-6">
        {{ config('app.name') }} {{ config('app.version') }}
        <span class="mx-2">•</span>
        <a class="hover:text-gray-600" href="https://github.com/rickvanderwolk/happynotes" target="_blank" rel="noopener noreferrer">
            About
        </a>
    </div>
</div>
