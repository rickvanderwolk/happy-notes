<x-app-layout>
    <x-slot name="title">New note</x-slot>
    @push('head')
        <meta name="navigate-cache-control" content="no-cache">
    @endpush

    <x-slot name="header"></x-slot>

    <div class="container form-page-container">
        <div class="text-center mb-4">
            <h1 class="section-title">New ✨</h1>
        </div>

        <form id="new-note-form" action="{{ route('note.store') }}" method="POST" class="new-note-form">
            @csrf

            <div class="mb-4">
                <textarea
                    name="title"
                    data-cy="new-note-title"
                    class="form-control elegant-input input-large @error('title') is-invalid @enderror"
                    placeholder="💭 Write anything..."
                    rows="3"
                    autofocus
                    required
                    data-single-line
                >{{ old('title') }}</textarea>

                @error('title')
                    <div data-cy="new-note-title-error" class="input-error-message">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <livewire:emoji-filter
                    :storageKey="null"
                    :updateUser="false"
                />

                @error('selectedEmojis')
                    <div data-cy="new-note-emojis-error" class="input-error-message">{{ $message }}</div>
                @enderror
            </div>

            <input type="hidden" name="selectedEmojis" id="selectedEmojis">
        </form>
    </div>
</x-app-layout>
