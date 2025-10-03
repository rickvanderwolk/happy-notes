<x-app-layout>
    <div x-data @apply-filter.window="window.location.href = '{{ route('notes.show') }}'">
        <div class="text-center mb-4">
            <h2 class="text-2xl font-bold">Search Notes 🔍</h2>
            <p class="text-sm section-description-text mt-2">Find notes by searching their content</p>
        </div>

        <livewire:text-filter />
    </div>
</x-app-layout>
