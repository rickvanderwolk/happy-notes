<x-app-layout>
    <x-slot name="title">Edit emojis</x-slot>
    @push('head')
        <meta name="navigate-cache-control" content="no-cache">
    @endpush

    <x-slot name="header"></x-slot>

    <div class="container form-page-container">
        <div class="text-center mb-4">
            <h1 class="section-title">Emojis 🎨</h1>
        </div>

        <form id="emoji-form" action="{{ route('note.emojis.store', ['note' => $item->uuid]) }}" method="POST">
            @csrf

            <div class="mb-4">
                <livewire:emoji-filter
                    :storageKey="null"
                    :updateUser="false"
                    :customEmojis="$item->emojis"
                />

                @error('selectedEmojis')
                    <div data-cy="note-emojis-error" class="input-error-message">{{ $message }}</div>
                @enderror
            </div>

            <input type="hidden" name="selectedEmojis" id="selectedEmojis">
        </form>
    </div>

    @livewireScripts
</x-app-layout>
