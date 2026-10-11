<x-app-layout>
    <div class="container max-w-md mx-auto">
        <div class="menu-header">
            <a href="{{ route('notes.show') }}" class="menu-logo" aria-label="Back to notes">
                <img src="/logo-no-margin.png" alt="">
            </a>
            <h2 class="section-title">HappyNotes</h2>
        </div>

        <div class="menu-card-modern">
            <a href="{{ route('profile.show') }}" data-turbolinks="false" class="menu-item-modern">
                <div class="menu-item-content">
                    <span class="menu-item-title">
                        Account
                        <x-keyboard-hint key="A" />
                    </span>
                    <span class="menu-item-subtitle">Manage your profile & settings</span>
                </div>
                <i class="fa fa-user menu-item-icon"></i>
            </a>

            <a href="{{ route('insights.show') }}" class="menu-item-modern">
                <div class="menu-item-content">
                    <span class="menu-item-title">Insights</span>
                    <span class="menu-item-subtitle">A look at your note-taking habits</span>
                </div>
                <i class="fa fa-lightbulb menu-item-icon"></i>
            </a>

            <a href="{{ route('shortcuts.show') }}" data-turbolinks="false" class="menu-item-modern menu-item-desktop-only">
                <div class="menu-item-content">
                    <span class="menu-item-title">
                        Keyboard Shortcuts
                        <x-keyboard-hint key="?" />
                    </span>
                    <span class="menu-item-subtitle">View all available shortcuts</span>
                </div>
                <i class="fa fa-keyboard menu-item-icon"></i>
            </a>

            <a href="https://github.com/rickvanderwolk/happynotes" target="_blank" rel="noopener noreferrer" data-turbolinks="false" class="menu-item-modern">
                <div class="menu-item-content">
                    <span class="menu-item-title">About</span>
                    <span class="menu-item-subtitle">Learn more about HappyNotes</span>
                </div>
                <i class="fa fa-info-circle menu-item-icon"></i>
            </a>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="menu-logout">
            @csrf
            {{-- The L shortcut clicks this button --}}
            <button id="logout-button" type="submit" class="btn btn-outline btn-block btn-lg">
                <i class="fa fa-sign-out"></i>Log out
                <x-keyboard-hint key="L" />
            </button>
        </form>

        <div class="app-version-text">
            {{ config('app.name') }} {{ config('app.version') }}
        </div>
    </div>
</x-app-layout>
