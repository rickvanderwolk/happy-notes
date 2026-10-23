<x-guest-layout>
    <x-slot name="title">Confirm password</x-slot>
    <x-authentication-card>
        <x-slot name="logo">
            <a href="{{ route('home') }}">
                <img class="logo auth-logo" src="/logo.png" alt="HappyNotes">
            </a>
        </x-slot>

        <div class="auth-intro">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </div>

        <x-validation-errors />

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div>
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" type="password" name="password" required autocomplete="current-password" autofocus />
            </div>

            <div class="auth-row auth-row-end">
                <x-button>
                    {{ __('Confirm') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
