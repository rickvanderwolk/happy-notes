<x-guest-layout>
    <div>

        <x-authentication-card>

            <x-slot name="logo">
                <img class="logo auth-logo" src="/logo.png" alt="HappyNotes">
            </x-slot>

            <h2 class="auth-title">Welcome back! 👋</h2>
            <p class="auth-subtitle mb-6">Sign in to {{ config('app.name') }}</p>

            <x-validation-errors class="mb-4" />

            @session('status')
                <div class="mb-4 font-medium text-sm text-green-600">
                    {{ $value }}
                </div>
            @endsession

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div>
                    <x-label for="email" value="{{ __('Email') }}" />
                    <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                </div>

                <div class="mt-4">
                    <x-label for="password" value="{{ __('Password') }}" />
                    <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
                </div>

                <div class="flex items-center justify-between mt-4">
                    <label for="remember_me" class="flex items-center cursor-pointer" style="padding-top: 7px;">
                        <x-checkbox id="remember_me" name="remember" />
                        <span class="ms-2 text-sm text-gray-600 hover:text-gray-900">{{ __('Remember me') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm text-gray-600 hover:text-gray-900 no-underline" href="{{ route('password.request') }}">
                            {{ __('Forgot password?') }}
                        </a>
                    @endif
                </div>

                <div class="mt-6">
                    <x-button class="w-full justify-center">
                        {{ __('Log in') }}
                    </x-button>
                </div>

                <div class="text-center mt-6">
                    <p class="text-sm text-gray-600">
                        Don't have an account?
                        <a class="text-primary hover:text-primary-dark font-semibold" href="{{ route('register') }}">
                            {{ __('Sign up') }}
                        </a>
                    </p>
                </div>
            </form>
        </x-authentication-card>
    </div>
</x-guest-layout>
