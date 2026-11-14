<x-app-layout>
    <x-slot name="title">Notes</x-slot>
    <x-slot name="header"></x-slot>

    <h1 class="visually-hidden">Notes</h1>

    @include('notes.partials.celebration')

    <div>
        @if($shuffleSeed ?? null)
            <a href="{{ route('notes.unshuffle') }}" class="shuffle-label" data-cy="shuffle-label" aria-label="Shuffled, back to the most recent first">
                <span aria-hidden="true">🎲</span> Shuffled <x-icon name="close" />
            </a>
        @endif

        @if(isset($notes) && $notes->count() > 0)
            <div id="note-list" data-cy="note-list" class="notes-list" data-shuffle="{{ $shuffleSeed ?? 0 }}">
                @include('notes.partials.cards', ['notes' => $notes])
            </div>

            <!-- Loading indicator -->
            <div id="loading" class="text-center py-4" role="status" style="display: none;">
                <span>Loading...</span>
            </div>
        @else
            <div class="empty-state">
                <img src="/logo.png" alt="HappyNotes" class="empty-state-logo" style="width: 150px; height: auto; margin: 0 auto 24px; display: block;">
                <h2 class="empty-state-title">No notes yet</h2>
                <p class="empty-state-subtitle">Create your first note</p>
                <a href="{{ route('note.create') }}" class="btn btn-primary mt-4">
                    <x-icon name="plus" />Create your first note
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
