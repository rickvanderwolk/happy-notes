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
        saveTimer: null,

        init() {
            this.syncInput()
        },

        isHidden(emoji) {
            return this.picked.includes(emoji) || (this.tracksOther && this.other.includes(emoji))
        },

        select(emoji) {
            if (this.picked.includes(emoji)) return
            this.other = this.other.filter(e => e !== emoji)
            this.picked.push(emoji)
            this.changed()
        },

        deselect(emoji) {
            this.picked = this.picked.filter(e => e !== emoji)
            this.changed()
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
            this.$wire.persist(this.picked, this.other)
        },
    }"
    @turbolinks:before-visit.window="flush()"
    @pagehide.window="flush()"
    @keydown.window.backspace="if (!['INPUT', 'TEXTAREA', 'SELECT'].includes($event.target.tagName)) deselectAll()"
>
    <div class="selected-emojis-container" x-show="picked.length" @style(['display: none' => empty($this->emojis)])>
        <div class="emoji-chips-wrapper">
            <template x-for="emoji in picked" :key="emoji">
                <div class="emoji-chip" @click="deselect(emoji)">
                    <span class="emoji" x-text="emoji"></span>
                    <span class="remove-badge">×</span>
                </div>
            </template>
        </div>
    </div>

    <div class="text-center mb-4" x-show="picked.length" @style(['display: none' => empty($this->emojis)])>
        <button type="button" @click="deselectAll()" class="btn btn-outline">
            <i class="fa fa-times-circle"></i>Clear (Backspace)
        </button>
    </div>

    {{-- The full list is rendered once and never re-rendered; picking only toggles
         visibility client side. --}}
    <div data-cy="emoji-filter-emoji-selector" class="emoji-grid">
        @foreach($this->allEmojis as $emoji)
            <div
                class="emoji-selector emoji-selector-item text-center"
                data-emoji="{{ $emoji }}"
                x-show="!isHidden($el.dataset.emoji)"
                @click="select($el.dataset.emoji)"
                @style(['display: none' => in_array($emoji, $hidden, true)])
            >
                <span class="emoji">{{ $emoji }}</span>
            </div>
        @endforeach
    </div>
</div>
