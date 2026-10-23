<x-guest-layout>
    <x-slot name="title">Forgot password</x-slot>
    <x-authentication-card>
        <x-slot name="logo">
            <a href="{{ route('home') }}">
                <img class="logo auth-logo" src="/logo.png" alt="HappyNotes">
            </a>
        </x-slot>

        <h1 class="auth-title">Forgot your password? 🔑</h1>
        <p class="auth-subtitle">No worries! We'll send you reset instructions</p>

        @session('status')
            <div class="auth-status">
                {{ $value }}
            </div>
        @endsession

        <x-validation-errors />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="auth-submit">
                <x-button>
                    {{ __('Send Reset Link') }}
                </x-button>
            </div>

            <div class="auth-footer">
                <a class="auth-text auth-text-link" href="{{ route('login') }}">
                    <x-icon name="arrow-left" /> {{ __('Back to login') }}
                </a>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
