<x-app-layout>
    @push('head')
        <meta name="turbolinks-cache-control" content="no-cache">
    @endpush

    <x-slot name="header"></x-slot>

    <div class="container form-page-container">
        <div class="text-center mb-4">
            <h2 class="section-title">Emojis 🎨</h2>
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
            <div class="fixed-button-inner">
                <button data-cy="save-note-emojis" type="submit" form="emoji-form" class="btn btn-success btn-block btn-lg">
                    <i class="fa fa-check"></i>Save Note
                </button>
            </div>
        </div>
    </div>

    @livewireScripts
</x-app-layout>
