<x-guest-layout>
    <x-slot name="title">Log in</x-slot>
    <div>

        <x-authentication-card>

            <x-slot name="logo">
                <a href="{{ route('home') }}">
                    <img class="logo auth-logo" src="/logo.png" alt="HappyNotes">
                </a>
            </x-slot>

            <h1 class="auth-title">Welcome back! 👋</h1>
            <p class="auth-subtitle">Sign in to {{ config('app.name') }}</p>

            <x-validation-errors />

            @session('status')
                <div class="auth-status">
                    {{ $value }}
                </div>
            @endsession

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div>
                    <x-label for="email" value="{{ __('Email') }}" />
                    <x-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                </div>

                <div class="auth-field">
                    <x-label for="password" value="{{ __('Password') }}" />
                    <x-input id="password" type="password" name="password" required autocomplete="current-password" />
                </div>

                <div class="auth-row">
                    <label for="remember_me" class="auth-remember">
                        <x-checkbox id="remember_me" name="remember" />
                        <span class="auth-text">{{ __('Remember me') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="auth-text auth-text-link" href="{{ route('password.request') }}">
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>

                <div class="auth-submit">
                    <x-button>
                        {{ __('Log in') }}
                    </x-button>
                </div>

                <div class="auth-footer">
                    <p class="auth-text">
                        Don't have an account?
                        <a class="auth-strong-link" href="{{ route('register') }}">
                            {{ __('Sign up') }}
                        </a>
                    </p>
                </div>
            </form>
        </x-authentication-card>
    </div>
</x-guest-layout>
