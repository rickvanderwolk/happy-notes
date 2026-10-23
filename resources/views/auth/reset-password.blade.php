<x-guest-layout>
    <x-slot name="title">Reset password</x-slot>
    <x-authentication-card>
        <x-slot name="logo">
            <a href="{{ route('home') }}">
                <img class="logo auth-logo" src="/logo.png" alt="HappyNotes">
            </a>
        </x-slot>

        <x-validation-errors />

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            </div>

            <div class="auth-field">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" type="password" name="password" required autocomplete="new-password" />
            </div>

            <div class="auth-field">
                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            <div class="auth-row auth-row-end">
                <x-button>
                    {{ __('Reset Password') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
