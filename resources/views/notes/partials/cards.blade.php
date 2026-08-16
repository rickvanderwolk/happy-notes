{{-- Shared by the full list page and the infinite scroll endpoint, so a scroll batch
     costs just these cards instead of a whole rendered page. --}}
@foreach($notes as $note)
    <a
        id="note-{{ $note->uuid }}"
        href="{{ route('note.show', $note->uuid) }}"
        data-cy="note-list-item"
        class="note-card"
    >
        <div class="note-title">{{ $note->title }}</div>

        @if($note->progress)
            <div class="note-progress">
                <x-progress-bar :progress="$note->progress" />
            </div>
        @endif

        @if(!empty($note->emojis))
            <div data-cy="emoji-wrapper" class="note-emojis">
                <x-emoji-list :emojis="$note->emojis" size="small" />
            </div>
        @endif
    </a>
@endforeach
