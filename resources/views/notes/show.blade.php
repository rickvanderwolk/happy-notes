<x-app-layout>
    <x-slot name="header"></x-slot>

    <div>
        @if($note)
            <div id="note-content">
                <div class="note-detail-header mb-4">
                    <h1 class="note-detail-title">
                        <a
                            href="{{ route('note.title.show', ['note' => $note->uuid]) }}"
                            data-cy="note-title"
                            class="plain-link cursor-pointer hover-scale-sm transition"
                        >{{ $note->title }}</a>
                    </h1>

                    <div data-cy="note-progress-bar" class="my-3">
                        <livewire:progress-bar :idNote="$note->id" :progress="$note->progress" />
                    </div>

                    <a
                        href="{{ route('note.emojis.show', ['note' => $note->uuid]) }}"
                        data-cy="note-emoji-wrapper"
                        class="plain-link"
                    >
                        <x-emoji-list :emojis="$note->emojis" class="emoji-wrapper-left cursor-pointer" />
                    </a>
                </div>

                <div class="col-12">
                    <div id="toc-container" style="display: none;">
                        <ul id="toc"></ul>
                    </div>
                </div>

                <div class="col-12">
                    <form id="postForm" action="{{ route('note.body.store', ['note' => $note->uuid]) }}" method="POST">
                        @csrf
                        <input type="hidden" id="body" name="body">

                        {{-- Unlike the note list, the body genuinely is not here yet: the
                             editor builds it client side. This holds the space until
                             EditorJS reports ready, and removes itself then. --}}
                        <div id="editor-placeholder" class="editor-placeholder" aria-hidden="true">
                            <div class="editor-placeholder-line" style="width: 95%;"></div>
                            <div class="editor-placeholder-line" style="width: 88%;"></div>
                            <div class="editor-placeholder-line" style="width: 92%;"></div>
                            <div class="editor-placeholder-line" style="width: 70%;"></div>
                        </div>

                        <div id="editorjs" data-cy="note-body" data-note-uuid="{{ $note->uuid }}" data-save-body-url="{{ route('note.body.store', ['note' => $note->uuid]) }}" data-initial-data="{{ json_encode($note->body) }}"></div>
                        @vite('resources/js/editor.js')
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
