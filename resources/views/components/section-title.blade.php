<div class="mb-6">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h3 class="account-card-title">{{ $title }}</h3>
            <p class="account-card-description">{{ $description }}</p>
        </div>

        @if(isset($aside))
            <div>
                {{ $aside }}
            </div>
        @endif
    </div>
</div>
