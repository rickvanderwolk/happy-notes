@props(['route' => null, 'active' => false, 'icon' => null, 'label' => '', 'showBadge' => false])

<a href="{{ $route }}" class="{{ $active ? 'active' : '' }}" aria-label="{{ $label }}">
    <div class="position-relative d-inline-block">
        @if($icon)
            <i class="fa fa-{{ $icon }}"></i>
        @endif
        {{ $slot }}
        @if($showBadge)
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-secondary border border-light rounded-circle notifiation-badge">
                <span class="visually-hidden">Active filter</span>
            </span>
        @endif
    </div>
</a>
