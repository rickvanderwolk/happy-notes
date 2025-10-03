@props(['emojis' => [], 'size' => 'default', 'clickable' => false, 'onClick' => null])

@php
    $sizeClass = match($size) {
        'small' => 'emoji-wrapper-sm',
        'large' => 'emoji-wrapper-xl',
        default => ''
    };
@endphp

<div class="emoji-wrapper {{ $sizeClass }} {{ $clickable ? 'clickable' : '' }}" {{ $attributes }}>
    @foreach($emojis as $emojiIndex => $emoji)
        <span class="emoji" @if($clickable && $onClick) onclick="{{ $onClick }}" @endif>{{ $emoji }}</span>
    @endforeach
</div>
