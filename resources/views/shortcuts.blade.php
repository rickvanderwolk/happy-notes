<x-app-layout>
    <div class="container max-w-md mx-auto">
        <div class="text-center mb-6">
            <h2 class="section-title">Keyboard Shortcuts ⌨️</h2>
            <p class="section-description-text">Speed up your workflow with these keyboard shortcuts</p>
        </div>

        {{-- Navigation --}}
        <div class="shortcuts-section">
            <h3 class="shortcuts-section-title">Navigation</h3>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Open menu
                    <span class="shortcut-context">from notes list</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">M</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Create new note
                    <span class="shortcut-context">from notes list</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">N</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">Go back / Close</div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">ESC</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Open note 1-9
                    <span class="shortcut-context">from notes list</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">1</span>
                    <span>-</span>
                    <span class="keyboard-key">9</span>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="shortcuts-section">
            <h3 class="shortcuts-section-title">Filters & Search</h3>

            <div class="shortcut-item">
                <div class="shortcut-description">Filter by emoji</div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">F</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">Exclude by emoji</div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">E</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">Search notes</div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">S</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Apply filter
                    <span class="shortcut-context">in filter views</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">ENTER</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Clear all
                    <span class="shortcut-context">in filter views</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">BACKSPACE</span>
                </div>
            </div>
        </div>

        {{-- Note Editing --}}
        <div class="shortcuts-section">
            <h3 class="shortcuts-section-title">Note Editing</h3>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Edit title
                    <span class="shortcut-context">in note view</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">T</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Edit emojis
                    <span class="shortcut-context">in note view</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">E</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Edit note body
                    <span class="shortcut-context">in note view</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">B</span>
                </div>
            </div>
        </div>

        {{-- Menu Actions --}}
        <div class="shortcuts-section">
            <h3 class="shortcuts-section-title">Menu Actions</h3>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Open account
                    <span class="shortcut-context">from menu</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">A</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Logout
                    <span class="shortcut-context">from menu</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">L</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">Show this help</div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">?</span>
                </div>
            </div>
        </div>

        <div class="text-center mt-6">
            <p class="section-description-text">
                💡 <strong>Tip:</strong> Most shortcuts work context-sensitive, adapting to the current page you're viewing.
            </p>
        </div>
    </div>
</x-app-layout>
