@if (session('celebration'))
    <div
        class="celebration"
        data-celebration
        data-emojis="{{ json_encode(session('celebration.emojis', []), JSON_UNESCAPED_UNICODE) }}"
        role="dialog"
        aria-modal="true"
        aria-labelledby="celebration-title"
    >
        <div class="celebration-card">
            <h2 id="celebration-title" data-cy="celebration-title" class="celebration-title">{{ session('celebration.title') }}</h2>
            <p class="celebration-text">{{ session('celebration.text') }}</p>
            <button type="button" class="btn btn-primary celebration-close" data-celebration-close aria-label="Close">🎉</button>
        </div>
    </div>
@endif
