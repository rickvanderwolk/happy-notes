<x-app-layout>
    @php
        $storageKey = request()->is('filter') ? 'selected_emojis' : 'excluded_emojis';
        $isInclude = request()->is('filter');
    @endphp

    <div class="container" x-data @apply-filter.window="window.location.href = '{{ route('notes.show') }}'">
        <div class="text-center mb-4">
            <h2 class="text-2xl font-bold">
                @if($isInclude)
                    Filter Notes ✨
                @else
                    Exclude Notes 🚫
                @endif
            </h2>
            <p class="text-sm text-gray-600 mt-2">
                @if($isInclude)
                    Select emojis to show only notes with these emojis
                @else
                    Select emojis to hide notes with these emojis
                @endif
            </p>
        </div>

        <livewire:emoji-filter :storageKey="$storageKey" :updateUser="true" />
    </div>
</x-app-layout>
