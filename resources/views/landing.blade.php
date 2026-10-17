<x-guest-layout>
    <div class="landing">
        <header class="landing-hero">
            <img class="logo landing-logo" src="/logo.png" alt="Happy Notes">

            <h1 class="landing-title">{{ config('app.name') }}</h1>

            <p class="landing-tagline">
                A note-taking app for <span class="landing-strikethrough">chaotic</span> free-spirited minds
            </p>

            <p class="landing-promise">
                No folders, no tags, just emojis ✨
            </p>

            @php
                $demoNotes = collect([
                    ['Birthday present for Noor', ['🎁', '💡'], null],
                    ['Workbench for the shed', ['🔨', '🏡'], 60],
                    ['Weekend in Ghent', ['🧳', '💡'], null],
                    ['Neighbourhood tool library', ['🔨', '🏡', '💡'], null],
                    ['Short story about a lighthouse', ['✍️', '💡'], null],
                    ["Things to say at Jip's wedding", ['✍️', '🎁'], null],
                    ['Paint the front door yellow', ['🏡', '💡'], null],
                    ['Christmas gifts for the family', ['🎁'], 25],
                    ['Road trip through Norway', ['🧳', '✍️'], null],
                    ['Bird house for Dad', ['🔨', '🎁'], null],
                    ['Zine about our street', ['✍️', '🏡'], null],
                    ['Packing list for camping', ['🧳'], 40],
                ])->map(fn ($note) => new \App\Models\Note([
                    'title' => $note[0],
                    'emojis' => json_encode($note[1], JSON_UNESCAPED_UNICODE),
                    'progress' => $note[2],
                ]));
                $demoEmojis = $demoNotes->flatMap(fn ($note) => $note->emojis)->countBy()->sortDesc()->keys();
            @endphp

            <div
                class="landing-demo"
                x-data="{
                    picked: [],
                    notes: @js($demoNotes->pluck('emojis')),
                    shows(emojis) { return this.picked.every(e => emojis.includes(e)) },
                    available(emoji) { return !this.picked.includes(emoji) && this.notes.some(n => this.shows(n) && n.includes(emoji)) },
                    get count() { return this.notes.filter(n => this.shows(n)).length },
                }"
                @keydown.window.backspace="picked = []"
                x-effect="$el.querySelectorAll('.note-card').forEach(card => card.style.display = shows(JSON.parse(card.dataset.emojis)) ? '' : 'none')"
            >
                <div class="selected-emojis-container" x-show="picked.length" style="display: none">
                    <div class="emoji-chips-wrapper">
                        <template x-for="emoji in picked" :key="emoji">
                            <div class="emoji-chip" @click="picked = picked.filter(e => e !== emoji)">
                                <span class="emoji" x-text="emoji"></span>
                                <span class="remove-badge">×</span>
                            </div>
                        </template>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm landing-demo-clear" @click="picked = []">Clear all</button>
                </div>

                <div class="landing-demo-picker">
                    @foreach ($demoEmojis as $emoji)
                        <div class="emoji-selector-item" data-emoji="{{ $emoji }}" x-show="available($el.dataset.emoji)" @click="picked.push($el.dataset.emoji)">
                            <span class="emoji">{{ $emoji }}</span>
                        </div>
                    @endforeach
                </div>

                <p class="landing-demo-hint" x-text="picked.length ? `${count} of ${notes.length} notes` + (count > 1 ? ' · tap another to narrow it down' : '') : `${notes.length} notes · tap an emoji`">{{ $demoNotes->count() }} notes · tap an emoji</p>

                <div class="landing-demo-notes" :class="{ 'is-stacked': count > 4 }">
                    @include('notes.partials.cards', ['notes' => $demoNotes, 'linked' => false])
                </div>
            </div>

            <div class="landing-buttons">
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-primary">Sign up</a>
                    <a href="{{ route('login') }}" class="btn btn-secondary">Log in</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Log in</a>
                @endif
            </div>
        </header>

        <section class="landing-section">
            <div class="landing-screenshots">
                @foreach ([
                    '1-notes-list' => 'The note list, every note with its emojis',
                    '2-filter' => 'Filtering notes by the 💡 emoji',
                    '3-note' => 'A note with a checklist',
                ] as $screenshot => $alt)
                    <picture>
                        <source srcset="/images/screenshot-{{ $screenshot }}-v4-dark.png" media="(prefers-color-scheme: dark)">
                        <img src="/images/screenshot-{{ $screenshot }}-v4-light.png" alt="{{ $alt }}" width="424" height="750" loading="lazy">
                    </picture>
                @endforeach
            </div>
        </section>

        <section class="landing-section">
            <h2 class="landing-heading">Why</h2>

            <div class="landing-story">
                <p>No note-taking app ever felt natural to me. I tried paper, various apps, and other tools, but I always lost track. Either too messy or too rigid.</p>
                <p>So I built my own: simple, visual, emoji-based. Now, I can finally organize my ideas in a way that works for me.</p>
                <p class="landing-signature">Rick</p>
            </div>
        </section>

        <section class="landing-section">
            <h2 class="landing-heading">Your notes are yours</h2>

            <div class="landing-steps">
                <div class="landing-step">
                    <div class="landing-step-emoji">🔓</div>
                    <h3>Fully open source</h3>
                    <p>Free, and the code is public. Anyone can check it or run their own copy.</p>
                </div>
                <div class="landing-step">
                    <div class="landing-step-emoji">📦</div>
                    <h3>Export anytime</h3>
                    <p>You can always export your notes, currently as CSV or JSON.</p>
                </div>
                <div class="landing-step">
                    <div class="landing-step-emoji">🔐</div>
                    <h3>Just your notes</h3>
                    <p>No ads, no tracking.@if (config('app.official_instance')) Hosted in the Netherlands (EU).@endif Two-factor login if you want it.</p>
                </div>
            </div>
        </section>

        <footer class="landing-footer">
            @if (Route::has('register'))
                <a href="{{ route('register') }}" class="btn btn-primary">Give it a try</a>
            @endif
            <a href="https://github.com/rickvanderwolk/happynotes" target="_blank" rel="noopener noreferrer" class="landing-github">
                View source on GitHub
            </a>
        </footer>
    </div>
</x-guest-layout>
