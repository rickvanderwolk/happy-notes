<x-account-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">

        <div class="account-header">
            <a href="{{ route('notes.show') }}">
                <img class="logo logo-sm mx-auto mb-4" src="/logo.png" alt="HappyNotes">
            </a>

            <h1>Account Management 🎨</h1>

            <a href="{{ route('dashboard') }}" class="btn btn-outline mt-4">
                ← Back to app
            </a>

            <x-section-border />
        </div>

        @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                @livewire('profile.update-profile-information-form')

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.update-password-form')
                </div>

                <x-section-border />
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="mt-10 sm:mt-0">
                    @livewire('profile.two-factor-authentication-form')
                </div>

                <x-section-border />
            @endif

            <div class="mt-10 sm:mt-0">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>

            <x-section-border />
            <div class="mt-10 sm:mt-0">
                @livewire('export-user-data-form')
            </div>

            @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <x-section-border />

                <div class="mt-10 sm:mt-0">
                    @livewire('profile.delete-user-form')
                </div>
            @endif

            <div class="text-center">
                <x-section-border />
                <a href="{{ route('dashboard') }}" class="btn btn-outline">
                    ← Back to app
                </a>
            </div>

        </div>
    </div>
</x-account-layout>
