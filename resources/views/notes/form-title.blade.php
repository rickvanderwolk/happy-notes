<x-app-layout>
    <x-slot name="title">Edit title</x-slot>
    <x-slot name="header"></x-slot>

    <div class="container form-page-container">
        <div class="text-center mb-4">
            <h1 class="section-title">Title ✏️</h1>
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
                    data-single-line
                >{{ $item->title }}</textarea>
            </div>
        </form>
    </div>
</x-app-layout>
