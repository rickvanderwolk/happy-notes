<x-app-layout>
    <div class="container max-w-md mx-auto">
        <div class="text-center mb-6">
            <a href="{{ route('notes.show') }}">
                <img src="/logo.png" alt="HappyNotes" class="logo" style="width: 100px; height: auto; margin: 0 auto 16px; display: block;">
            </a>
            <h2 class="text-2xl font-bold">Menu</h2>
        </div>

        <div class="menu-card-modern">
            <a href="{{ route('profile.show') }}" data-turbolinks="false" class="menu-item-modern">
                <div class="menu-item-content">
                    <span class="menu-item-title">Account</span>
                    <span class="menu-item-subtitle">Manage your profile & settings</span>
                </div>
                <i class="fa fa-user menu-item-icon"></i>
            </a>

            <a href="https://github.com/rickvanderwolk/happynotes" target="_blank" rel="noopener noreferrer" data-turbolinks="false" class="menu-item-modern">
                <div class="menu-item-content">
                    <span class="menu-item-title">About</span>
                    <span class="menu-item-subtitle">Learn more about HappyNotes</span>
                </div>
                <i class="fa fa-info-circle menu-item-icon"></i>
            </a>

            <form id="logout-button" method="POST" action="{{ route('logout') }}" class="menu-item-modern" style="cursor: pointer;" onclick="this.submit()">
                @csrf
                <div class="menu-item-content">
                    <span class="menu-item-title">Logout</span>
                    <span class="menu-item-subtitle">Sign out of your account</span>
                </div>
                <i class="fa fa-sign-out menu-item-icon"></i>
            </form>
        </div>

        <div class="text-center text-xs text-gray-400" style="margin-top: 80px;">
            {{ config('app.name') }} {{ config('app.version') }}
        </div>
    </div>
</x-app-layout>
