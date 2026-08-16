@props([
    'route' => null,
    'active' => false,
    'icon' => null,
    'label' => '',
    'showBadge' => false,
    // Optional Alpine expression. When given, the badge follows it client side instead of
    // needing a server roundtrip to appear or disappear.
    'badgeShow' => null,
])

<a href="{{ $route }}" class="{{ $active ? 'active' : '' }}" aria-label="{{ $label }}">
    <div class="position-relative d-inline-block">
        @if($icon)
            <i class="fa fa-{{ $icon }}"></i>
        @endif
        {{ $slot }}
        @if($showBadge || $badgeShow)
            <span
                class="position-absolute top-0 start-100 translate-middle p-1 bg-secondary border border-light rounded-circle notifiation-badge"
                @if($badgeShow)
                    x-show="{{ $badgeShow }}"
                    @style(['display: none' => !$showBadge])
                @endif
            >
                <span class="visually-hidden">Active filter</span>
            </span>
        @endif
    </div>
</a>
