<x-app-layout>
    <x-slot name="header"></x-slot>

    <div class="container" style="padding-bottom: 100px;">
        <div class="text-center mb-4">
            <h2 class="text-2xl font-bold">Edit Emojis 🎨</h2>
        </div>

        <form id="emoji-form" action="{{ route('note.emojis.store', ['note' => $item->uuid]) }}" method="POST">
            @csrf

            <div class="mb-4">
                <livewire:emoji-filter
                    :storageKey="null"
                    :updateUser="false"
                    :customEmojis="$item->emojis"
                />
            </div>

            <input type="hidden" name="selectedEmojis" id="selectedEmojis">
        </form>

        <div class="fixed-button-wrapper">
            <div style="max-width: 500px; margin: 0 auto; padding: 0 16px;">
                <button data-cy="save-note-emojis" type="submit" form="emoji-form" class="btn btn-success btn-block btn-lg">
                    Save Note ✓
                </button>
            </div>
        </div>
    </div>

    @livewireScripts
</x-app-layout>
