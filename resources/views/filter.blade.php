<x-app-layout>
    <x-slot name="title">{{ request()->is('filter') ? 'Filter' : 'Exclude' }}</x-slot>
    @push('head')
        <meta name="navigate-cache-control" content="no-cache">
    @endpush

    @php
        $storageKey = request()->is('filter') ? 'selected_emojis' : 'excluded_emojis';
        $isInclude = request()->is('filter');
    @endphp

    <div x-data
         @apply-filter.window="Livewire.navigate('{{ route('notes.show') }}')"
         @keydown.window.enter="Livewire.navigate('{{ route('notes.show') }}')">
        <div class="text-center mb-4">
            <h2 class="section-title">
                @if($isInclude)
                    Filter ✨
                @else
                    Exclude 🚫
                @endif
            </h2>
            <p class="section-description-text mt-2">
                @if($isInclude)
                    Only show notes with these emojis
                @else
                    Hide notes with these emojis
                @endif
            </p>
        </div>

        <livewire:emoji-filter :storageKey="$storageKey" :updateUser="true" />
    </div>
</x-app-layout>
