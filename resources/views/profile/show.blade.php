<x-account-layout>
    <x-slot name="title">Account</x-slot>
    <div class="container mx-auto">
        {{-- Page header --}}
        <div class="text-center">
            <h1 class="section-title"><x-icon name="gear" class="me-2" />Account</h1>
            <p class="section-description-text">Manage your profile and settings</p>
        </div>

        {{-- Account sections --}}
        @if (Laravel\Fortify\Features::canUpdateProfileInformation())
            @livewire('profile.update-profile-information-form')
        @endif

        @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
            @livewire('profile.update-password-form')
        @endif

        @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
            @livewire('profile.two-factor-authentication-form')
        @endif

        @livewire('profile.logout-other-browser-sessions-form')

        @livewire('export-user-data-form')

        @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
            @livewire('profile.delete-user-form')
        @endif

        {{-- App version --}}
        <div class="app-version-text">
            {{ config('app.name') }} {{ config('app.version') }}
        </div>
    </div>
</x-account-layout>
