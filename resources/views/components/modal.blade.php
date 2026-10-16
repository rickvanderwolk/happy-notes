@props(['id', 'maxWidth'])

@php
$id = $id ?? md5($attributes->wire('model'));

$maxWidth = [
    'sm' => '400px',
    'md' => '500px',
    'lg' => '600px',
    'xl' => '700px',
    '2xl' => '800px',
][$maxWidth ?? 'md'];
@endphp

<div
    x-data="{ show: @entangle($attributes->wire('model')) }"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    id="{{ $id }}"
    style="display: none;"
    x-transition
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        class="jet-modal-backdrop"
        x-on:click="show = false"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    {{-- Modal Content --}}
    <div
        x-show="show"
        class="jet-modal-content"
        style="max-width: {{ $maxWidth }};"
        x-trap.inert.noscroll="show"
        x-transition:enter="transition-all ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-8"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition-all ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-8"
    >
        {{ $slot }}
    </div>
</div>

