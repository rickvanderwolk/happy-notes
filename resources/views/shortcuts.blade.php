<x-app-layout>
    <x-slot name="title">Keyboard shortcuts</x-slot>
    <div class="container mx-auto">
        <div class="text-center">
            <h1 class="section-title"><x-icon name="keyboard" class="me-2" />Keyboard shortcuts</h1>
            <p class="section-description-text">Speed up your workflow with these keyboard shortcuts</p>
        </div>

        <div class="form-switch shortcuts-toggle" x-data="{ on: window.keyboardShortcutsEnabled() }">
            <input
                class="form-check-input"
                type="checkbox"
                id="keyboard-shortcuts-toggle"
                x-model="on"
                @change="window.setKeyboardShortcuts(on)"
            >
            <label class="form-check-label" for="keyboard-shortcuts-toggle">Single-key shortcuts on this device</label>
        </div>

        {{-- Navigation --}}
        <div class="shortcuts-section">
            <h2 class="shortcuts-section-title">Navigation</h2>

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
            <h2 class="shortcuts-section-title">Filters & search</h2>

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

        {{-- Note editing --}}
        <div class="shortcuts-section">
            <h2 class="shortcuts-section-title">Note editing</h2>

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
                    Edit body
                    <span class="shortcut-context">in note view</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">B</span>
                </div>
            </div>
        </div>

        {{-- Menu actions --}}
        <div class="shortcuts-section">
            <h2 class="shortcuts-section-title">Menu actions</h2>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Shuffle notes
                    <span class="shortcut-context">from list or menu</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">H</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Stop shuffling
                    <span class="shortcut-context">in a shuffled list</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">ESC</span>
                </div>
            </div>

            <div class="shortcut-item">
                <div class="shortcut-description">
                    Open insights
                    <span class="shortcut-context">from menu</span>
                </div>
                <div class="shortcut-keys">
                    <span class="keyboard-key">I</span>
                </div>
            </div>

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
                    Log out
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

        <div class="text-center">
            <p class="section-description-text">
                <x-icon name="lightbulb" class="me-2" /><strong>Tip:</strong> What a shortcut does can depend on the page you're on.
            </p>
        </div>
    </div>
</x-app-layout>
