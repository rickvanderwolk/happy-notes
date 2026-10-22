{{-- The term stays client side while typing and is saved once, when leaving. A debounced
     live update would still fire after the page has been swapped out, and a request sent
     while leaving would be dropped with the old page, so hold the navigation until it is saved. --}}
<div
    x-data="{ saved: false }"
    x-on:livewire:navigate.window="
        if (!saved && !$event.detail.history) {
            $event.preventDefault();
            saved = true;
            $wire.$commit().finally(() => Livewire.navigate($event.detail.url.href));
        }
    "
>
    <div class="mb-4 input-group">
        <textarea
            wire:model="search_query"
            class="form-control elegant-input input-large"
            placeholder="🔍 Search notes..."
            aria-label="Search notes"
            rows="1"
            autofocus
            @keydown.enter.prevent="Livewire.navigate('{{ route('notes.show') }}')"
        ></textarea>
        <button
            x-show="$wire.search_query"
            @style(['display: none' => ! $search_query])
            wire:click="$set('search_query', '')"
            class="input-clear"
            type="button"
            aria-label="Clear search"
        >
            ×
        </button>
    </div>

    <div class="form-switch mb-4">
        <input wire:model.live="search_query_only" class="form-check-input" type="checkbox" id="customSwitch">
        <label class="form-check-label" for="customSwitch">Search by text only (ignore other filters)</label>
    </div>
</div>
