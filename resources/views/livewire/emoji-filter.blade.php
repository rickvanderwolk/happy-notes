@php
    $initialSuggestions = $this->initialSuggestions;
    $hidden = array_merge($this->hiddenEmojis, $initialSuggestions);
@endphp

<div
    x-data="{
        picked: @js($this->emojis),
        other: @js($this->otherEmojis),
        tracksOther: @js($this->tracksOtherList()),
        persist: @js($this->updateUser),
        storageKey: @js($this->storageKey),
        saveUrl: @js(route('filter.emojis.store')),
        csrfToken: @js(csrf_token()),
        saveTimer: null,
        saving: Promise.resolve(),
        savesInFlight: 0,
        dataUrl: @js($this->dataUrl),
        dataLoaded: false,
        emojiSets: [],
        setsByEmoji: new Map(),
        overallShare: null,
        wordScores: {},
        emojiPairs: {},
        contextText: @js($this->contextText),
        ranked: @js($this->allEmojis),
        suggested: @js($initialSuggestions),
        reachable: null,
        refreshTimer: null,

        init() {
            this.contextText ||= document.querySelector('textarea[name=title]')?.value ?? ''
            this.syncInput()
            this.refresh()
            setTimeout(() => this.loadData())
        },

        loadData() {
            if (!this.dataUrl) return
            const store = window.emojiSuggestionData ??= {}
            const scope = new URL(this.dataUrl).pathname
            const apply = data => {
                this.useData(data)
                this.dataLoaded = true
                this.refresh()
            }

            if (store[scope]?.url === this.dataUrl) return apply(store[scope].data)

            fetch(this.dataUrl, { headers: { 'Accept': 'application/json' } })
                .then(response => response.ok ? response.json() : Promise.reject())
                .then(data => {
                    store[scope] = { url: this.dataUrl, data }
                    apply(data)
                })
                .catch(() => {})
        },

        useData({ sets = [], words = {}, pairs = {} }) {
            const setsByEmoji = new Map()
            for (const set of sets) {
                for (const emoji of set.e) {
                    if (!setsByEmoji.has(emoji)) setsByEmoji.set(emoji, [])
                    setsByEmoji.get(emoji).push(set)
                }
            }
            this.emojiSets = sets
            this.setsByEmoji = setsByEmoji
            this.overallShare = this.share(sets)
            this.wordScores = words
            this.emojiPairs = pairs
        },

        share(sets) {
            let total = 0
            const counts = new Map()
            for (const set of sets) {
                total += set.n
                for (const emoji of set.e) counts.set(emoji, (counts.get(emoji) ?? 0) + set.n)
            }
            return { total, counts }
        },

        queueRefresh() {
            clearTimeout(this.refreshTimer)
            this.refreshTimer = setTimeout(() => this.refresh(), 250)
        },

        refresh() {
            clearTimeout(this.refreshTimer)
            if (this.storageKey === 'excluded_emojis') return
            this.refreshReachable()
            this.refreshSuggestions()
        },

        refreshReachable() {
            if (this.storageKey !== 'selected_emojis' || !this.dataLoaded) return
            const reachable = new Set()
            this.filterMatches().forEach(set => set.e.forEach(e => reachable.add(e)))
            this.reachable = reachable
        },

        filterMatches() {
            const picked = [...this.picked]
            const other = [...this.other]
            const setsByEmoji = Alpine.raw(this.setsByEmoji)
            let candidates = Alpine.raw(this.emojiSets)
            for (const emoji of picked) {
                const sets = setsByEmoji.get(emoji) ?? []
                if (sets.length < candidates.length) candidates = sets
            }
            return candidates.filter(set =>
                picked.every(e => set.e.includes(e)) && !other.some(e => set.e.includes(e))
            )
        },

        isUnreachable(emoji) {
            return this.reachable !== null && !this.reachable.has(emoji)
        },

        refreshSuggestions() {
            const usable = emoji => !this.picked.includes(emoji) && !this.isUnreachable(emoji)
            const ranked = Alpine.raw(this.ranked).filter(usable)
            const fixed = ranked.slice(0, 2)
            const titleScores = this.titleScores()
            const pickScores = this.pickScores()
            const smart = [...new Set([...titleScores.keys(), ...pickScores.keys()])]
                .filter(emoji => usable(emoji) && !fixed.includes(emoji))
                .map(emoji => [emoji, (titleScores.get(emoji) ?? 0) + 2 * (pickScores.get(emoji) ?? 0)])
                .sort((a, b) => b[1] - a[1])
                .slice(0, 3)
                .map(([emoji]) => emoji)
            const next = [...new Set([...fixed, ...smart, ...ranked])].slice(0, 5)

            if (next.join() !== [...this.suggested].join()) this.suggested = next
        },

        titleScores() {
            const scores = new Map()
            const tokens = this.contextText.toLowerCase().match(/[\p{L}\p{N}]{3,}/gu) ?? []
            const words = Alpine.raw(this.wordScores)

            for (const token of tokens) {
                for (const [emoji, score] of Object.entries(words[token] ?? {})) {
                    scores.set(emoji, Math.max(scores.get(emoji) ?? 0, score))
                }
            }

            return scores
        },

        pickScores() {
            const scores = new Map()
            if (!this.picked.length || !this.dataLoaded) return scores

            if (!this.tracksOther) {
                const pairs = Alpine.raw(this.emojiPairs)
                for (const picked of this.picked) {
                    for (const [emoji, score] of Object.entries(pairs[picked] ?? {})) {
                        scores.set(emoji, Math.max(scores.get(emoji) ?? 0, score))
                    }
                }
                return scores
            }

            const overall = Alpine.raw(this.overallShare)
            const near = this.share(this.filterMatches())
            near.counts.forEach((count, emoji) => {
                const score = count / near.total - overall.counts.get(emoji) / overall.total
                if (score >= 0.1) scores.set(emoji, score)
            })

            return scores
        },

        isHidden(emoji) {
            return this.picked.includes(emoji)
                || this.suggested.includes(emoji)
                || (this.tracksOther && this.other.includes(emoji))
        },

        select(emoji, el) {
            if (this.picked.includes(emoji)) return
            this.keepFocus(el)
            this.other = this.other.filter(e => e !== emoji)
            this.picked.push(emoji)
            this.changed()
        },

        deselect(emoji, el) {
            this.keepFocus(el)
            this.picked = this.picked.filter(e => e !== emoji)
            this.changed()
        },

        /* A picked emoji disappears from where it was, so keyboard focus moves on to its
           neighbour instead of falling back to the top of the page. */
        keepFocus(el) {
            if (!el || el !== document.activeElement) return
            const parent = el.parentElement
            const index = [...parent.children].indexOf(el)
            this.$nextTick(() => {
                const items = [...parent.children].filter(item => item !== el && item.matches('button') && item.offsetParent !== null)
                const next = items.find(item => [...parent.children].indexOf(item) >= index)
                    ?? items.at(-1)
                    ?? [...this.$root.querySelectorAll('.emoji-grid button')].find(item => item.offsetParent !== null)
                next?.focus()
            })
        },

        deselectAll() {
            if (!this.picked.length) return
            this.picked = []
            this.changed()
        },

        changed() {
            this.syncInput()
            this.refresh()
            window.dispatchEvent(new CustomEvent('emoji-filter-changed', {
                detail: { storageKey: this.storageKey, picked: [...this.picked], other: [...this.other] }
            }))
            if (this.persist) this.queueSave()
        },

        /* The note forms read the selection from a hidden input on submit. */
        syncInput() {
            const input = document.getElementById('selectedEmojis')
            if (input) input.value = JSON.stringify(this.picked)
        },

        queueSave() {
            clearTimeout(this.saveTimer)
            this.saveTimer = setTimeout(() => this.flush(), 300)
        },

        /* Navigating away inside the debounce window would otherwise drop the change. */
        flush() {
            if (!this.persist || !this.saveTimer) return
            clearTimeout(this.saveTimer)
            this.saveTimer = null
            this.save()
        },

        /* Saves go out one after another, so an earlier one can never land after a later
           one. keepalive so the request still completes if the page is going away. */
        save() {
            const body = JSON.stringify({
                storageKey: this.storageKey,
                emojis: this.picked,
                otherEmojis: this.other,
            })
            this.savesInFlight++
            this.saving = this.saving
                .catch(() => {})
                .then(() => fetch(this.saveUrl, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                    },
                    body,
                }))
                .finally(() => this.savesInFlight--)
        },

        /* The next page reads the filters from the server, so it waits for the last save.
           Otherwise the server can render it before that save is stored, with the old
           selection. Back and forward are left alone. */
        beforeNavigate(event) {
            this.flush()
            if (!this.savesInFlight || event.detail.history) return
            event.preventDefault()
            const url = event.detail.url.href
            this.saving.finally(() => window.Livewire.navigate(url))
        },
    }"
    x-on:livewire:navigate.window="beforeNavigate($event)"
    @pagehide.window="flush()"
    @input.window="if ($event.target.name === 'title') { contextText = $event.target.value; queueRefresh() }"
    @keydown.window.backspace="if (!['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName)) deselectAll()"
>
    <div class="selected-emojis-container" x-show="picked.length" @style(['display: none' => empty($this->emojis)])>
        <div class="emoji-chips-wrapper">
            <template x-for="emoji in picked" :key="emoji">
                <button type="button" class="emoji-chip" :aria-label="'Remove ' + emoji" @mousedown.prevent @click="deselect(emoji, $el)">
                    <span class="emoji" x-text="emoji"></span>
                    <span class="remove-badge" aria-hidden="true">×</span>
                </button>
            </template>
        </div>
    </div>

    <div class="text-center mb-4" x-show="picked.length" @style(['display: none' => empty($this->emojis)])>
        <button type="button" @mousedown.prevent @click="deselectAll()" class="btn btn-outline">
            <x-icon name="times-circle" />Clear<span class="keyboard-hint">&nbsp;(Backspace)</span>
        </button>
    </div>

    @if($this->storageKey !== 'excluded_emojis')
        <div data-cy="emoji-suggestions" class="emoji-suggestions" role="group" aria-labelledby="emoji-suggestions-label" x-show="suggested.length" @style(['display: none' => empty($initialSuggestions)])>
            <p id="emoji-suggestions-label" class="emoji-picker-label">Suggested</p>
            <div class="emoji-suggestions-row">
                <template x-for="emoji in suggested" :key="emoji">
                    <button
                        type="button"
                        class="emoji-selector emoji-selector-item text-center"
                        :aria-label="'Add ' + emoji"
                        @mousedown.prevent
                        @click="select(emoji, $el)"
                    >
                        <span class="emoji" x-text="emoji"></span>
                    </button>
                </template>
            </div>
        </div>
        <p class="emoji-picker-label" x-show="suggested.length && ranked.some(e => !isHidden(e))" @style(['display: none' => empty($initialSuggestions) || count($this->allEmojis) <= count($hidden)])>More</p>
    @endif

    {{-- The full list is rendered once and never re-rendered; picking only toggles
         visibility client side. mousedown.prevent keeps the focus where it was, so on the
         new note page typing carries on in the title instead of hitting the shortcuts. --}}
    <div data-cy="emoji-filter-emoji-selector" class="emoji-grid">
        @foreach($this->allEmojis as $emoji)
            <button
                type="button"
                class="emoji-selector emoji-selector-item text-center"
                data-emoji="{{ $emoji }}"
                x-show="!isHidden($el.dataset.emoji)"
                @if($this->storageKey === 'selected_emojis')
                    :class="{ 'is-unreachable': isUnreachable($el.dataset.emoji) }"
                @endif
                @mousedown.prevent
                @click="select($el.dataset.emoji, $el)"
                @style(['display: none' => in_array($emoji, $hidden, true)])
            >
                <span class="emoji">{{ $emoji }}</span>
            </button>
        @endforeach
    </div>
</div>
