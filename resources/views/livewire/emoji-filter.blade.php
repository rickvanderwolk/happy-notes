<div wire:keydown.window.backspace="deselectAll" wire:keydown.window.enter="$dispatch('applyFilter')">
    <!-- Selected emojis - horizontaal naast elkaar -->
    @if(!empty($currentEmojis))
        <div class="selected-emojis mb-4 p-3" style="background: rgba(255, 217, 61, 0.1); border-radius: 12px; overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: nowrap; min-width: min-content;">
                @foreach($currentEmojis as $emoji)
                    <div
                        class="emoji-chip cursor-pointer transition hover-scale"
                        wire:click="deselectEmoji('{{ $emoji }}')"
                        style="background: rgba(255, 217, 61, 0.4); padding: 6px 10px; border-radius: 10px; position: relative; flex-shrink: 0; display: inline-flex; align-items: center; justify-content: center;"
                    >
                        <span class="emoji" style="font-size: 28px; line-height: 1;">{{ $emoji }}</span>
                        <span style="position: absolute; top: -6px; right: -6px; background: #FF6B6B; color: white; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">×</span>
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
        @foreach($selectableEmojis as $emoji)
            <div
                class="emoji-selector cursor-pointer transition text-center"
                wire:click="selectEmoji('{{ $emoji }}')"
                style="padding: 8px; border-radius: 8px; transition: all 0.2s ease;"
                onmouseover="this.style.background='rgba(255, 217, 61, 0.2)'; this.style.transform='scale(1.1)';"
                onmouseout="this.style.background='transparent'; this.style.transform='scale(1)';"
            >
                <span class="emoji" style="font-size: 32px; line-height: 1;">{{ $emoji }}</span>
            </div>
        @endforeach
    </div>
</div>
