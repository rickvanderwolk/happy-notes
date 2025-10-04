@props(['submit'])

<div class="account-section">
    <form wire:submit="{{ $submit }}">
        <div class="account-card">
            {{-- Section header --}}
            <div>
                <h3 class="account-card-title">{{ $title }}</h3>
                <p class="account-card-description">{{ $description }}</p>
            </div>

            {{-- Form fields --}}
            <div class="grid grid-cols-1">
                {{ $form }}
            </div>

            {{-- Actions --}}
            @if (isset($actions))
                <div class="section-actions">
                    {{ $actions }}
                </div>
            @endif
        </div>
    </form>
</div>
