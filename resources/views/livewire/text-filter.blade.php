<div>
    <div class="input-group mb-4">
        <span class="input-icon">🔍</span>
        <input
            type="text"
            wire:model.live="search_query"
            class="form-control"
            placeholder="Search notes..."
            autofocus
            style="padding-left: 42px; font-size: 18px;"
            wire:keydown.enter="applyFilter"
        >
        @if($search_query)
            <button wire:click="$set('search_query', '')" class="input-clear" type="button">
                ×
            </button>
        @endif
    </div>

    <div class="form-switch mb-4">
        <input wire:model.live="search_query_only" class="form-check-input" type="checkbox" id="customSwitch">
        <label class="form-check-label" for="customSwitch">Search only (ignore other filters)</label>
    </div>

    @if($search_query && isset($resultCount))
        <div class="text-center p-3 bg-gray-100 rounded-lg">
            <span class="text-sm text-gray-600">
                Found <strong>{{ $resultCount }}</strong> note{{ $resultCount !== 1 ? 's' : '' }}
            </span>
        </div>
    @endif
</div>
