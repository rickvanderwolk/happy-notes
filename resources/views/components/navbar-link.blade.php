@props(['route' => null, 'active' => false, 'icon' => null, 'label' => '', 'dataCy' => null])

<a
    href="{{ $route }}"
    class="{{ $active ? 'active' : '' }}"
    aria-label="{{ $label }}"
    @if($dataCy) data-cy="{{ $dataCy }}" @endif
>
    @if($icon)
        <i class="fa fa-{{ $icon }}"></i>
    @endif
    {{ $slot }}
</a>
