{{-- Shared by the full list page and the infinite scroll endpoint, so a scroll batch
     costs just these cards instead of a whole rendered page. The landing page demo
     passes linked => false to get the same cards without links. --}}
{{-- Emojis being filtered on come first, so a note always shows why it is in the list --}}
@php
    $linked = $linked ?? true;
    $filterEmojis = auth()->user()?->selected_emojis ?? [];
@endphp
@foreach($notes as $note)
    @if($linked)
        <a
            id="note-{{ $note->uuid }}"
            href="{{ route('note.show', $note->uuid) }}"
            data-cy="note-list-item"
            class="note-card"
        >
    @else
        <div class="note-card" data-emojis='@json($note->emojis ?? [])'>
    @endif
        <div class="note-title" @if($linked) wire:transition.navigate="note-{{ $note->uuid }}" @endif>{{ $note->title }}</div>

        @if($note->progress)
            <div class="note-progress">
                <x-progress-bar :progress="$note->progress" />
            </div>
        @endif

        @if(!empty($note->emojis))
            {{-- One line of emojis keeps every note the same height: 8 on a phone, 14 where
                 the line is longer, and a count for the rest. The note itself shows them all. --}}
            @php
                $filtered = array_values(array_intersect($note->emojis, $filterEmojis));
                $ordered = array_merge($filtered, array_values(array_diff($note->emojis, $filtered)));
                $moreOnPhone = count($ordered) - 8;
                $moreWide = count($ordered) - 14;
            @endphp
            <div data-cy="emoji-wrapper" class="note-emojis">
                <div class="emoji-wrapper emoji-wrapper-sm">
                    @foreach(array_slice($ordered, 0, 14) as $index => $emoji)
                        <span @class(['emoji', 'emoji-wide-only' => $index >= 8])>{{ $emoji }}</span>
                    @endforeach
                </div>
                @if($moreOnPhone > 0)
                    <span class="note-emojis-more note-emojis-more-phone" aria-label="and {{ $moreOnPhone }} more">+{{ $moreOnPhone }}</span>
                @endif
                @if($moreWide > 0)
                    <span class="note-emojis-more note-emojis-more-wide" aria-label="and {{ $moreWide }} more">+{{ $moreWide }}</span>
                @endif
            </div>
        @endif
    @if($linked)
        </a>
    @else
        </div>
    @endif
@endforeach
