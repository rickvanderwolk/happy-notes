<x-guest-layout>
    <x-slot name="title">Register</x-slot>
    <x-authentication-card>
        <x-slot name="logo">
            <a href="{{ route('home') }}">
                <img class="logo auth-logo" src="/logo.png" alt="HappyNotes">
            </a>
        </x-slot>

        <h1 class="auth-title">Join {{ config('app.name') }}! 🎉</h1>
        <p class="auth-subtitle">Create your account to get started</p>

        <x-validation-errors />

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div>
                <x-label for="name" value="{{ __('Name') }}" />
                <x-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            </div>

            <div class="auth-field">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            </div>

            <div class="auth-field">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div class="auth-field">
                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            <div class="auth-submit">
                <x-button>
                    {{ __('Create Account') }}
                </x-button>
            </div>

            <div class="auth-footer">
                <p class="auth-text">
                    Already have an account?
                    <a class="auth-strong-link" href="{{ route('login') }}">
                        {{ __('Sign in') }}
                    </a>
                </p>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
