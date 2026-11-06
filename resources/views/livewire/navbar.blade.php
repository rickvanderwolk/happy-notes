<nav id="main-navbar" class="main-navbar" data-cy="main-navbar" aria-label="Main">
    {{-- On larger screens the logo crowns the rail; it hops now and then --}}
    <a href="{{ route('notes.show') }}" class="rail-logo" aria-label="Back to notes">
        <img src="/logo.png" alt="">
    </a>
    <div class="navbar-inner">
        <div class="row g-0">
            <div class="col-12">
                @php
                    $currentRouteName = session('original_route_name', '');
                    $note = request()->route('note');
                    $uuidFromRoute = is_object($note) ? $note->uuid : (is_array($note) ? $note['uuid'] : null);

                    // Determine close URL
                    $closeUrl = route('notes.show');
                    if (Str::contains($currentRouteName, 'form.') && $uuidFromRoute) {
                        $closeUrl = route('note.show', ['note' => $uuidFromRoute]);
                    } elseif ($uuidFromRoute) {
                        $closeUrl = route('notes.show') . "#note-{$uuidFromRoute}";
                    }
                @endphp

                @if(request()->routeIs('note.show'))
                    {{-- Note detail view --}}
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            <div class="emoji-wrapper quick-actions" wire:transition.navigate="pill">
                                {{-- The note saves as you type, so leaving it is being done --}}
                                <a href="{{ $closeUrl }}" class="quick-new" aria-label="Done">
                                    <x-icon name="check" />
                                </a>
                                <form action="{{ route('note.destroy', ['note' => $uuidFromRoute]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button data-cy="delete-note" type="submit" aria-label="Delete note"
                                            data-confirm="Delete this note?"
                                            data-confirm-label="Delete"
                                            data-confirm-danger>
                                        <x-icon name="trash-can" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                @elseif(request()->routeIs('note.create'))
                    {{-- New note: closing and saving float together, like on the list --}}
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            <div class="emoji-wrapper quick-actions" wire:transition.navigate="pill">
                                <button data-cy="save-new-note" type="submit" form="new-note-form" class="quick-new quick-save" aria-label="Save"
                                        data-leave-if-empty="{{ $closeUrl }}">
                                    <x-icon name="check" /><span class="quick-save-label">Save</span>
                                </button>
                                <a href="{{ $closeUrl }}" aria-label="Close"
                                   data-confirm="Discard this note?"
                                   data-confirm-label="Discard"
                                   data-confirm-danger
                                   data-confirm-if-filled="new-note-form">
                                    <x-icon name="close" />
                                </a>
                            </div>
                        </div>
                    </div>

                @elseif(request()->routeIs('notes.show'))
                    {{-- Notes list view --}}
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            {{-- Every page has the same pill: at the bottom within thumb reach on
                                 phones, as a rail next to the column on larger screens. Its first
                                 button, in yellow, is always the obvious next step (new note here,
                                 save, or done); it comes first for the keyboard too, and the pill
                                 shows it at the end, right under the thumb. --}}
                            <div class="emoji-wrapper quick-actions" wire:transition.navigate="pill">
                                <a href="{{ route('note.create') }}" class="quick-new" aria-label="New note" data-cy="create-new-note">
                                    <x-icon name="plus" />
                                </a>
                                @if(count($selectedEmojis ?? []) > 0)
                                    {{-- Like the notes in the list: a few emojis and a count for the rest --}}
                                    <a href="{{ route('filter.show') }}" class="quick-filter" aria-label="Filter">
                                        @foreach(array_slice($selectedEmojis, 0, 3) as $emoji)
                                            <span class="emoji">{{ $emoji }}</span>
                                        @endforeach
                                        @if(count($selectedEmojis) > 3)
                                            <span class="quick-filter-more" aria-label="and {{ count($selectedEmojis) - 3 }} more">+{{ count($selectedEmojis) - 3 }}</span>
                                        @endif
                                    </a>
                                @else
                                    {{-- Excluding or searching filters the list too; the dot says so --}}
                                    <x-navbar-link-with-badge :route="route('filter.show')" :active="false" icon="filter" label="Filter"
                                        :showBadge="count($excludedEmojis ?? []) > 0 || !empty($searchQuery ?? '')" />
                                @endif
                                <x-navbar-link :route="route('menu.show')" icon="bars" label="Menu" />
                            </div>
                        </div>
                    </div>

                @elseif(Str::contains($currentRouteName, 'filter'))
                    {{-- Filter views --}}
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            {{-- The emoji filter reports its selection client side, so these
                                 badges follow along without costing a server roundtrip per
                                 click. --}}
                            <div
                                class="emoji-wrapper quick-actions" wire:transition.navigate="pill"
                                x-data="{
                                    selected: @js(count($selectedEmojis ?? [])),
                                    excluded: @js(count($excludedEmojis ?? [])),
                                }"
                                @emoji-filter-changed.window="
                                    if ($event.detail.storageKey === 'selected_emojis') {
                                        selected = $event.detail.picked.length
                                        excluded = $event.detail.other.length
                                    } else if ($event.detail.storageKey === 'excluded_emojis') {
                                        excluded = $event.detail.picked.length
                                        selected = $event.detail.other.length
                                    }
                                "
                            >
                                {{-- The filter applies as you pick, so leaving it is being done --}}
                                <a href="{{ route('notes.show') }}" class="quick-new" aria-label="Done">
                                    <x-icon name="check" />
                                </a>
                                <x-navbar-link-with-badge
                                    :route="route('filter.search.show')"
                                    :active="$currentRouteName === 'filter.search.show'"
                                    icon="search"
                                    label="Filter - search text"
                                    :showBadge="!empty($searchQuery ?? '')"
                                />
                                <x-navbar-link-with-badge
                                    :route="route('filter.exclude.show')"
                                    :active="$currentRouteName === 'filter.exclude.show'"
                                    icon="ban"
                                    label="Filter - exclude emojis"
                                    :showBadge="count($excludedEmojis ?? []) > 0"
                                    badgeShow="excluded > 0"
                                />
                                <x-navbar-link-with-badge
                                    :route="route('filter.show')"
                                    :active="$currentRouteName === 'filter.show'"
                                    icon="filter"
                                    label="Filter - include emojis"
                                    :showBadge="count($selectedEmojis ?? []) > 0"
                                    badgeShow="selected > 0"
                                />
                            </div>
                        </div>
                    </div>

                @elseif(request()->routeIs('note.title.show', 'note.emojis.show'))
                    {{-- Editing a note's title or emojis: back to the note, or save --}}
                    @php
                        $isTitleForm = request()->routeIs('note.title.show');
                    @endphp
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            <div class="emoji-wrapper quick-actions" wire:transition.navigate="pill">
                                <button data-cy="{{ $isTitleForm ? 'save-note' : 'save-note-emojis' }}" type="submit"
                                        form="{{ $isTitleForm ? 'title-form' : 'emoji-form' }}"
                                        class="quick-new quick-save" aria-label="Save">
                                    <x-icon name="check" /><span class="quick-save-label">Save</span>
                                </button>
                                <a href="{{ route('note.show', ['note' => $uuidFromRoute]) }}" aria-label="Close">
                                    <x-icon name="close" />
                                </a>
                            </div>
                        </div>
                    </div>

                @else
                    {{-- Menu and other pages: closing is the next step --}}
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            <div class="emoji-wrapper quick-actions" wire:transition.navigate="pill">
                                <a href="{{ $closeUrl }}" class="quick-new" aria-label="Close">
                                    <x-icon name="close" />
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</nav>
