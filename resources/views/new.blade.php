<x-app-layout>
    <x-slot name="header"></x-slot>

    <meta name="notes-url" content="{{ url('/') }}">

    <div class="container" style="padding-bottom: 100px;">
        <div class="text-center mb-4">
            <h2 class="text-2xl font-bold">Create a New Note ✨</h2>
        </div>

        <form action="{{ route('note.store') }}" method="POST" class="new-note-form">
            @csrf

            <div class="mb-4">
                <textarea
                    name="title"
                    data-cy="new-note-title"
                    class="form-control elegant-input"
                    placeholder="💭 What's on your mind?"
                    rows="3"
                    autofocus
                    required
                    style="font-size: 20px; font-weight: 500;"
                    onkeydown="if(event.key === 'Enter') event.preventDefault();"
                ></textarea>
            </div>

            <div class="mb-4">
                <livewire:emoji-filter
                    :storageKey="null"
                    :updateUser="false"
                />
            </div>

            <input type="hidden" name="selectedEmojis" id="selectedEmojis">
        </form>

        <div class="fixed-button-wrapper">
            <div style="max-width: 500px; margin: 0 auto; padding: 0 16px;">
                <button data-cy="save-new-note" type="submit" form="note.store" onclick="document.querySelector('form').submit()" class="btn btn-success btn-block btn-lg">
                    Save Note ✓
                </button>
            </div>
        </div>
    </div>

    @livewireScripts
</x-app-layout>
