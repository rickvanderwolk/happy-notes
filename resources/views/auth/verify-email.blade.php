<x-guest-layout>
    <x-slot name="title">Verify email</x-slot>
    <x-authentication-card>
        <x-slot name="logo">
            <a href="{{ route('home') }}">
                <img class="logo auth-logo" src="/logo.png" alt="HappyNotes">
            </a>
        </x-slot>

        <div class="auth-intro">
            {{ __('Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="auth-status">
                {{ __('A new verification link has been sent to the email address you provided in your profile settings.') }}
            </div>
        @endif

        <div class="auth-row">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <div>
                    <x-button type="submit">
                        {{ __('Resend Verification Email') }}
                    </x-button>
                </div>
            </form>

            <div>
                <a
                    href="{{ route('profile.show') }}"
                    class="auth-text auth-text-link auth-underline"
                >
                    {{ __('Edit Profile') }}</a>

                <form method="POST" action="{{ route('logout') }}" class="auth-inline">
                    @csrf

                    <button type="submit" class="auth-text auth-text-link auth-underline auth-inline-gap">
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>
