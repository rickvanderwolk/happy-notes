@php
    $hidden = $this->hiddenEmojis;
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

        init() {
            this.syncInput()
        },

        isHidden(emoji) {
            return this.picked.includes(emoji) || (this.tracksOther && this.other.includes(emoji))
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
            <x-icon name="times-circle" />Clear (Backspace)
        </button>
    </div>

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
                @mousedown.prevent
                @click="select($el.dataset.emoji, $el)"
                @style(['display: none' => in_array($emoji, $hidden, true)])
            >
                <span class="emoji">{{ $emoji }}</span>
            </button>
        @endforeach
    </div>
</div>
