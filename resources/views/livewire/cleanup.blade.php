<div>
    @if(empty($emojiStats))
        <div class="text-center">
            <p class="section-description-text">No emojis found</p>
        </div>
    @else
        <div class="cleanup-list">
            @foreach($emojiStats as $emoji => $data)
                <div class="cleanup-item">
                    <div class="cleanup-emoji-info">
                        <span class="emoji">{{ $emoji }}</span>
                        <span class="cleanup-count">{{ $data['count'] }} {{ $data['count'] === 1 ? 'note' : 'notes' }}</span>
                    </div>
                    <div class="cleanup-actions">
                        <button wire:click="filterByEmoji('{{ $emoji }}')" class="btn btn-sm btn-outline">
                            Filter
                        </button>
                        @if($data['canRemove'])
                            <button wire:click="removeEmoji('{{ $emoji }}')" wire:confirm="Are you sure you want to remove {{ $emoji }} from all notes?" class="btn btn-sm btn-outline btn-danger">
                                Remove
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
