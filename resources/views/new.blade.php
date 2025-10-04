<x-app-layout>
    @push('head')
        <meta name="turbolinks-cache-control" content="no-cache">
    @endpush

    <x-slot name="header"></x-slot>

    <meta name="notes-url" content="{{ url('/') }}">

    <div class="container form-page-container">
        <div class="text-center mb-4">
            <h2 class="section-title">New ✨</h2>
        </div>

        <form action="{{ route('note.store') }}" method="POST" class="new-note-form">
            @csrf

            <div class="mb-4">
                <textarea
                    name="title"
                    data-cy="new-note-title"
                    class="form-control elegant-input input-large"
                    placeholder="💭 Write anything..."
                    rows="3"
                    autofocus
                    required
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
            <div class="fixed-button-inner">
                <button data-cy="save-new-note" type="submit" form="note.store" onclick="document.querySelector('form').submit()" class="btn btn-success btn-block btn-lg">
                    <i class="fa fa-check"></i>Save Note
                </button>
            </div>
        </div>
    </div>

    @livewireScripts
</x-app-layout>
