@props(['emojis' => [], 'size' => 'default', 'clickable' => false])

@php
    $sizeClass = match($size) {
        'small' => 'emoji-wrapper-sm',
        'large' => 'emoji-wrapper-xl',
        default => ''
    };
@endphp

<div class="emoji-wrapper {{ $sizeClass }} {{ $clickable ? 'clickable' : '' }}" {{ $attributes }}>
    @foreach($emojis as $emojiIndex => $emoji)
        <span class="emoji">{{ $emoji }}</span>
    @endforeach
</div>
