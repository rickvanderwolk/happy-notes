@props(['name'])

<i {{ $attributes->merge(['class' => 'icon icon-' . $name, 'aria-hidden' => 'true']) }}>@include('components.icons.' . $name)</i>