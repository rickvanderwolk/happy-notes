<x-app-layout>
    <div class="container max-w-md mx-auto">
        <div class="text-center mb-6">
            <a href="{{ route('notes.show') }}">
                <img src="/logo.png" alt="HappyNotes" class="logo logo-menu">
            </a>
            <h2 class="section-title">Menu</h2>
        </div>

        <div class="menu-card-modern">
            <a href="{{ route('profile.show') }}" data-no-navigate class="menu-item-modern">
                <div class="menu-item-content">
                    <span class="menu-item-title">
                        Account
                        <x-keyboard-hint key="A" />
                    </span>
                    <span class="menu-item-subtitle">Manage your profile & settings</span>
                </div>
                <x-icon name="user" class="menu-item-icon" />
            </a>

            <a href="{{ route('insights.show') }}" class="menu-item-modern">
                <div class="menu-item-content">
                    <span class="menu-item-title">Insights</span>
                    <span class="menu-item-subtitle">A look at your note-taking habits</span>
                </div>
                <x-icon name="lightbulb" class="menu-item-icon" />
            </a>

            <a href="{{ route('shortcuts.show') }}" data-no-navigate class="menu-item-modern menu-item-desktop-only">
                <div class="menu-item-content">
                    <span class="menu-item-title">
                        Keyboard Shortcuts
                        <x-keyboard-hint key="?" />
                    </span>
                    <span class="menu-item-subtitle">View all available shortcuts</span>
                </div>
                <x-icon name="keyboard" class="menu-item-icon" />
            </a>

            <a href="https://github.com/rickvanderwolk/happynotes" target="_blank" rel="noopener noreferrer" data-no-navigate class="menu-item-modern">
                <div class="menu-item-content">
                    <span class="menu-item-title">About</span>
                    <span class="menu-item-subtitle">Learn more about HappyNotes</span>
                </div>
                <x-icon name="info-circle" class="menu-item-icon" />
            </a>

            <form id="logout-button" method="POST" action="{{ route('logout') }}" class="menu-item-modern" style="cursor: pointer;" onclick="this.submit()">
                @csrf
                <div class="menu-item-content">
                    <span class="menu-item-title">
                        Logout
                        <x-keyboard-hint key="L" />
                    </span>
                    <span class="menu-item-subtitle">Sign out of your account</span>
                </div>
                <x-icon name="sign-out" class="menu-item-icon" />
            </form>
        </div>

        <div class="app-version-text">
            {{ config('app.name') }} {{ config('app.version') }}
        </div>
    </div>
</x-app-layout>
