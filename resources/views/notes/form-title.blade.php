<x-app-layout>
    <x-slot name="header"></x-slot>

    <div class="container form-page-container">
        <div class="text-center mb-4">
            <h2 class="section-title">Edit Title ✏️</h2>
        </div>

        <form id="title-form" action="{{ route('note.title.store', ['note' => $item->uuid]) }}" method="POST">
            @csrf

            <div class="mb-4">
                <textarea
                    id="titleTextarea"
                    data-cy="note-title-editor"
                    name="title"
                    class="form-control elegant-input input-large"
                    placeholder="Title"
                    rows="3"
                    autofocus
                    required
                    onkeydown="if(event.key === 'Enter') event.preventDefault();"
                >{{ $item->title }}</textarea>
            </div>
        </form>

        <div class="fixed-button-wrapper">
            <div class="fixed-button-inner">
                <button data-cy="save-note" type="submit" form="title-form" class="btn btn-success btn-block btn-lg">
                    <i class="fa fa-check"></i>Save Note
                </button>
            </div>
        </div>
    </div>
</x-app-layout>
