<x-app-layout>
    <x-slot name="header"></x-slot>

    <div>
        @if(isset($notes) && $notes->count() > 0)
            <div id="note-list" data-cy="note-list" class="notes-list">
                @include('notes.partials.cards', ['notes' => $notes])
            </div>

            <!-- Loading indicator -->
            <div id="loading" class="text-center py-4" style="display: none;">
                <span class="text-sm text-gray-500">Loading...</span>
            </div>
        @else
            <div class="empty-state">
                <img src="/logo.png" alt="HappyNotes" class="empty-state-logo" style="width: 150px; height: auto; margin: 0 auto 24px; display: block;">
                <h3 class="empty-state-title">No notes yet</h3>
                <p class="empty-state-subtitle">Create your first note</p>
                <a href="{{ route('note.create') }}" class="btn btn-primary mt-4">
                    <i class="fa fa-plus"></i>Create your first note
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
