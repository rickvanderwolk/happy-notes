<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <img class="logo auth-logo" src="/logo.png" alt="HappyNotes">
        </x-slot>

        <h2 class="auth-title">Forgot your password? 🔑</h2>
        <p class="auth-subtitle mb-6">No worries! We'll send you reset instructions</p>

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="block">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="mt-6">
                <x-button class="w-full justify-center">
                    {{ __('Send Reset Link') }}
                </x-button>
            </div>

            <div class="text-center mt-6">
                <a class="text-sm text-gray-600 hover:text-gray-900" href="{{ route('login') }}">
                    <i class="fa fa-arrow-left"></i> {{ __('Back to login') }}
                </a>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
