<div wire:keydown.window.backspace="deselectAll">
    <!-- Selected emojis - horizontaal naast elkaar -->
    @if(!empty($currentEmojis))
        <div class="selected-emojis-container">
            <div class="emoji-chips-wrapper">
                @foreach($currentEmojis as $emoji)
                    <div class="emoji-chip" wire:click="deselectEmoji('{{ $emoji }}')">
                        <span class="emoji">{{ $emoji }}</span>
                        <span class="remove-badge">×</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(!empty($currentEmojis))
        <div class="text-center mb-4">
            <button wire:click="deselectAll" class="btn btn-outline">
                <i class="fa fa-times-circle"></i> Clear all (Backspace)
            </button>
        </div>
    @endif

    <!-- Emoji selector grid -->
    <div data-cy="emoji-filter-emoji-selector" class="emoji-grid">
        @foreach($this->selectableEmojis as $emoji)
            <div class="emoji-selector emoji-selector-item text-center" wire:click="selectEmoji('{{ $emoji }}')">
                <span class="emoji">{{ $emoji }}</span>
            </div>
        @endforeach
    </div>
</div>
