<x-app-layout>
    @push('head')
        <meta name="turbolinks-cache-control" content="no-cache">
    @endpush

    @php
        $storageKey = request()->is('filter') ? 'selected_emojis' : 'excluded_emojis';
        $isInclude = request()->is('filter');
    @endphp

    <div x-data
         @apply-filter.window="window.Turbolinks.visit('{{ route('notes.show') }}')"
         @keydown.window.enter="window.Turbolinks.visit('{{ route('notes.show') }}')">
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
