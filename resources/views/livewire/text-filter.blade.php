<div>
    <div class="mb-4 input-group">
        <textarea
            wire:model.live="search_query"
            class="form-control elegant-input input-large"
            placeholder="🔍 Search notes..."
            rows="1"
            autofocus
            @keydown.enter.prevent="window.Turbolinks.visit('{{ route('notes.show') }}')"
        ></textarea>
        @if($search_query)
            <button wire:click="$set('search_query', '')" class="input-clear" type="button">
                ×
            </button>
        @endif
    </div>

    <div class="form-switch mb-4">
        <input wire:model.live="search_query_only" class="form-check-input" type="checkbox" id="customSwitch">
        <label class="form-check-label" for="customSwitch">Search by text only (ignore other filters)</label>
    </div>

    @if($search_query && isset($resultCount))
        <div class="result-count-indicator">
            <span class="result-count-text">
                Found <strong>{{ $resultCount }}</strong> note{{ $resultCount !== 1 ? 's' : '' }}
            </span>
        </div>
    @endif
</div>
