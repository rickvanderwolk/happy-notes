<div id="main-navbar" class="main-navbar" data-cy="main-navbar">
    <div class="navbar-inner">
        <div class="row g-0">
            <div class="col-12">
                @php
                    $currentRouteName = session('original_route_name', '');
                    $note = request()->route('note');
                    $uuidFromRoute = is_object($note) ? $note->uuid : (is_array($note) ? $note['uuid'] : null);

                    // Determine close URL
                    $closeUrl = '/';
                    if (Str::contains($currentRouteName, 'form.') && $uuidFromRoute) {
                        $closeUrl = route('note.show', ['note' => $uuidFromRoute]);
                    } elseif ($uuidFromRoute) {
                        $closeUrl = url("/#note-{$uuidFromRoute}");
                    }
                @endphp

                @if(request()->routeIs('note.show'))
                    {{-- Note detail view --}}
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            <h3 class="emoji-wrapper">
                                <form action="{{ route('note.destroy', ['note' => $uuidFromRoute]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button data-cy="delete-note" type="submit"
                                            onclick="return confirm('Are you sure you want to delete this note?')">
                                        <i class="fa fa-trash-can"></i>
                                    </button>
                                </form>
                            </h3>
                            <h3 class="emoji-wrapper">
                                <x-navbar-link :route="$closeUrl" icon="close" label="Close" />
                            </h3>
                        </div>
                    </div>

                @elseif(request()->routeIs('notes.show'))
                    {{-- Notes list view --}}
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <h3 class="emoji-wrapper">
                                <x-navbar-link :route="route('menu.show')" :active="request()->is('menu')" icon="bars" label="Menu" />
                                <x-navbar-link :route="route('note.create')" :active="request()->is('new')" icon="plus" label="New note" data-cy="create-new-note" />
                            </h3>
                            <h3 class="emoji-wrapper">
                                @if(count($selectedEmojis ?? []) > 0)
                                    <a href="{{ route('filter.show') }}" class="{{ request()->is('filter') ? 'active' : '' }}" aria-label="Filter">
                                        @foreach(array_slice($selectedEmojis, 0, 3) as $emoji)
                                            <span class="emoji">{{ $emoji }}</span>
                                        @endforeach
                                        @if(count($selectedEmojis) > 3)
                                            <i class="fa fa-ellipsis"></i>
                                        @endif
                                    </a>
                                @else
                                    <x-navbar-link-with-badge :route="route('filter.show')" :active="request()->is('filter')" icon="filter" label="Filter" />
                                @endif
                            </h3>
                        </div>
                    </div>

                @elseif(Str::contains($currentRouteName, 'filter'))
                    {{-- Filter views --}}
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            {{-- The emoji filter reports its selection client side, so these
                                 badges follow along without costing a server roundtrip per
                                 click. --}}
                            <h3
                                class="emoji-wrapper"
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
                                <x-navbar-link-with-badge
                                    :route="route('filter.show')"
                                    :active="$currentRouteName === 'filter.show'"
                                    icon="filter"
                                    label="Filter - include emojis"
                                    :showBadge="count($selectedEmojis ?? []) > 0"
                                    badgeShow="selected > 0"
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
                                    :route="route('filter.search.show')"
                                    :active="$currentRouteName === 'filter.search.show'"
                                    icon="search"
                                    label="Filter - search text"
                                    :showBadge="!empty($searchQuery ?? '')"
                                />
                            </h3>
                            <h3 class="emoji-wrapper">
                                <x-navbar-link :route="url('/')" :active="request()->is('/')" icon="close" label="Close" />
                            </h3>
                        </div>
                    </div>

                @else
                    {{-- Other routes --}}
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            <h3 class="emoji-wrapper"></h3>
                            <h3 class="emoji-wrapper">
                                <x-navbar-link :route="$closeUrl" icon="close" label="Close" />
                            </h3>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
