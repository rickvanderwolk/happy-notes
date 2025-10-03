<div>
    <div class="mb-4" style="position: relative;">
        <textarea
            wire:model.live="search_query"
            class="form-control elegant-input"
            placeholder="🔍 Search notes..."
            rows="1"
            autofocus
            style="font-size: 20px; font-weight: 500;"
            wire:keydown.enter.prevent="applyFilter"
        ></textarea>
        @if($search_query)
            <button wire:click="$set('search_query', '')" class="input-clear" type="button" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%);">
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
