<x-app-layout>
    <x-slot name="header"></x-slot>

    <meta name="notes-url" content="{{ url('/') }}">

    <div>
        @if(isset($notes) && $notes->count() > 0)
            <!-- Skeleton loaders - shown initially, hidden by JavaScript after page load -->
            <div id="skeleton-container" class="notes-list">
                @for($i = 0; $i < 5; $i++)
                    <x-note-skeleton />
                @endfor
            </div>

            <!-- Real notes - hidden initially, shown by JavaScript after page load -->
            <div id="note-list" data-cy="note-list" class="notes-list" style="display: none;">
                @foreach($notes as $note)
                    <div
                        id="note-{{ $note->uuid }}"
                        data-cy="note-list-item"
                        class="note-card"
                        onclick="window.location.href='{{ route('note.show', $note->uuid) }}'"
                    >
                        <div class="note-title">{{ $note->title }}</div>

                        @if($note->progress)
                            <div class="note-progress">
                                <livewire:progress-bar :idNote="$note->id" :progress="$note->progress" />
                            </div>
                        @endif

                        @if(!empty($note->emojis))
                            <div data-cy="emoji-wrapper" class="note-emojis">
                                <x-emoji-list :emojis="$note->emojis" size="small" />
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Loading indicator -->
            <div id="loading" class="text-center py-4" style="display: none;">
                <span class="text-sm text-gray-500">Loading more notes...</span>
            </div>

            <div style="display: none;">
                {{ $notes->links() }}
            </div>
        @else
            <div class="empty-state">
                <img src="/logo.png" alt="HappyNotes" class="empty-state-logo" style="width: 150px; height: auto; margin: 0 auto 24px; display: block;">
                <h3 class="empty-state-title">No notes yet</h3>
                <p class="empty-state-subtitle">Start capturing your thoughts and ideas</p>
                <a href="{{ route('note.create') }}" class="btn btn-primary mt-4">
                    <i class="fa fa-plus"></i> Create your first note
                </a>
            </div>
        @endif
    </div>
</x-app-layout>
