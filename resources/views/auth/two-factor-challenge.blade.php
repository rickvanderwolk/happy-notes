<x-guest-layout>
    <x-slot name="title">Two-factor authentication</x-slot>
    <x-authentication-card>
        <x-slot name="logo">
            <a href="{{ route('home') }}">
                <img class="logo auth-logo" src="/logo.png" alt="HappyNotes">
            </a>
        </x-slot>

        <div x-data="{ recovery: false }">
            <div class="auth-intro" x-show="! recovery">
                {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
            </div>

            <div class="auth-intro" x-cloak x-show="recovery">
                {{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}
            </div>

            <x-validation-errors />

            <form method="POST" action="{{ route('two-factor.login') }}">
                @csrf

                <div class="auth-field" x-show="! recovery">
                    <x-label for="code" value="{{ __('Code') }}" />
                    <x-input id="code" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code" />
                </div>

                <div class="auth-field" x-cloak x-show="recovery">
                    <x-label for="recovery_code" value="{{ __('Recovery Code') }}" />
                    <x-input id="recovery_code" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code" />
                </div>

                <div class="auth-row auth-row-end">
                    <button type="button" class="auth-text auth-text-link auth-underline"
                                    x-show="! recovery"
                                    x-on:click="
                                        recovery = true;
                                        $nextTick(() => { $refs.recovery_code.focus() })
                                    ">
                        {{ __('Use a recovery code') }}
                    </button>

                    <button type="button" class="auth-text auth-text-link auth-underline"
                                    x-cloak
                                    x-show="recovery"
                                    x-on:click="
                                        recovery = false;
                                        $nextTick(() => { $refs.code.focus() })
                                    ">
                        {{ __('Use an authentication code') }}
                    </button>

                    <x-button>
                        {{ __('Log in') }}
                    </x-button>
                </div>
            </form>
        </div>
    </x-authentication-card>
</x-guest-layout>
