<div id="main-navbar" class="main-navbar" data-cy="main-navbar">
    <div class="navbar-inner">
        <div class="row g-0">
            <div class="col-12">
                @php
                    $currentRouteName = session('original_route_name', '');
                    $note = request()->route('note');
                    $uuidFromRoute = is_object($note) ? $note->uuid : (is_array($note) ? $note['uuid'] : null);
                @endphp

                @if((request()->routeIs( 'note.show')))
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            @if(request()->routeIs( 'note.show'))
                                <h3 class="emoji-wrapper">
                                    <form action="{{ route('note.destroy', ['note' => $uuidFromRoute]) }}"
                                          method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button data-cy="delete-note" type="submit"
                                                onclick="return confirm('Are you sure you want to delete this note?')">
                                            <i class="fa fa-trash-can"></i>
                                        </button>
                                    </form>
                                </h3>
                            @else
                                <h3 class="emoji-wrapper"></h3> <!-- Leeg voor form routes -->
                            @endif

                            @if(Str::contains($currentRouteName, 'form.'))
                                <h3 class="emoji-wrapper">
                                    <a href="{{ route('note.show', ['note' => $uuidFromRoute]) }}" aria-label="Close">
                                        <i class="fa fa-close"></i>
                                    </a>
                                </h3>
                            @elseif($uuidFromRoute !== null)
                                <h3 class="emoji-wrapper">
                                    <a href="{{ url("/#note-{$uuidFromRoute}") }}" aria-label="Close">
                                        <i class="fa fa-close"></i>
                                    </a>
                                </h3>
                            @else
                                <h3 class="emoji-wrapper">
                                    <a href="{{ url('/') }}" aria-label="Close">
                                        <i class="fa fa-close"></i>
                                    </a>
                                </h3>
                            @endif
                        </div>
                    </div>

                @elseif((request()->routeIs( 'notes.show')))
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <h3 class="emoji-wrapper">
                                <a href="{{ route('menu.show') }}" class="{{ request()->is('menu') ? 'active' : '' }}" aria-label="Menu">
                                    <i class="fa fa-bars"></i>
                                </a>
                                <a data-cy="create-new-note" href="{{ route('note.create') }}" class="{{ request()->is('new') ? 'active' : '' }}" aria-label="New note">
                                    <i class="fa fa-plus"></i>
                                </a>
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
                                    <a href="{{ route('filter.show') }}" class="{{ request()->is('filter') ? 'active' : '' }}" aria-label="Filter">
                                        <div class="position-relative d-inline-block">
                                            <i class="fa fa-filter"></i>
                                        </div>
                                    </a>
                                @endif
                            </h3>
                        </div>
                    </div>

                @elseif(Str::contains($currentRouteName, 'filter'))
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            <h3 class="emoji-wrapper">
                                <a href="{{ route('filter.show') }}" class="{{ $currentRouteName === 'filter.show' ? 'active' : '' }}" aria-label="Filter - include emojis">
                                    <div class="position-relative d-inline-block">
                                        <i class="fa fa-filter"></i>
                                        @if(count($selectedEmojis) > 0)
                                            <span
                                                class="position-absolute top-0 start-100 translate-middle p-1 bg-secondary border border-light rounded-circle notifiation-badge">
                                                <span class="visually-hidden">New notifications</span>
                                            </span>
                                        @endif
                                    </div>
                                </a>
                                <a href="{{ route('filter.exclude.show') }}" class="{{ $currentRouteName === 'filter.exclude.show' ? 'active' : '' }}" aria-label="Filter - exclude emojis">
                                    <div class="position-relative d-inline-block">
                                        <i class="fa fa-ban"></i>
                                        @if(count($excludedEmojis) > 0)
                                            <span
                                                class="position-absolute top-0 start-100 translate-middle p-1 bg-secondary border border-light rounded-circle notifiation-badge">
                                                <span class="visually-hidden">New notifications</span>
                                            </span>
                                        @endif
                                    </div>
                                </a>
                                <a href="{{ route('filter.search.show') }}" class="{{ $currentRouteName === 'filter.search.show' ? 'active' : '' }}" aria-label="Filter - Text">
                                    <div class="position-relative d-inline-block">
                                        <i class="fa fa-search"></i>
                                        @if(!empty($searchQuery))
                                            <span
                                                class="position-absolute top-0 start-100 translate-middle p-1 bg-secondary border border-light rounded-circle notifiation-badge">
                                                <span class="visually-hidden">New notifications</span>
                                            </span>
                                        @endif
                                    </div>
                                </a>
                            </h3>
                            <h3 class="emoji-wrapper">
                                <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}" aria-label="Close">
                                    <i class="fa fa-close"></i>
                                </a>
                            </h3>
                        </div>
                    </div>

                @else
                    <div class="row">
                        <div class="col-12 d-flex justify-content-between">
                            <h3 class="emoji-wrapper">
                                <!-- Leeg voor andere routes zonder specifieke actie -->
                            </h3>
                            <h3 class="emoji-wrapper">
                                @if($uuidFromRoute !== null)
                                    <h3 class="emoji-wrapper">
                                        <a href="{{ route('note.show', ['note' => $uuidFromRoute]) }}" aria-label="Close">
                                            <i class="fa fa-close"></i>
                                        </a>
                                    </h3>
                                @else
                                    <h3 class="emoji-wrapper">
                                        <a href="{{ url('/') }}" aria-label="Close">
                                            <i class="fa fa-close"></i>
                                        </a>
                                    </h3>
                                @endif
                            </h3>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>
