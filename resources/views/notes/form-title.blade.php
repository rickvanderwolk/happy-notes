<x-app-layout>
    <x-slot name="header"></x-slot>

    <div class="container" style="padding-bottom: 100px;">
        <div class="text-center mb-4">
            <h2 class="text-2xl font-bold">Edit Title ✏️</h2>
        </div>

        <form id="title-form" action="{{ route('note.title.store', ['note' => $item->uuid]) }}" method="POST">
            @csrf

            <div class="mb-4">
                <textarea
                    id="titleTextarea"
                    data-cy="note-title-editor"
                    name="title"
                    class="form-control elegant-input"
                    placeholder="Title"
                    rows="3"
                    autofocus
                    required
                    style="font-size: 20px; font-weight: 500;"
                    onkeydown="if(event.key === 'Enter') event.preventDefault();"
                >{{ $item->title }}</textarea>
            </div>
        </form>

        <div class="fixed-button-wrapper">
            <div style="max-width: 500px; margin: 0 auto; padding: 0 16px;">
                <button data-cy="save-note" type="submit" form="title-form" class="btn btn-success btn-block btn-lg">
                    Save Note ✓
                </button>
            </div>
        </div>
    </div>
</x-app-layout>
