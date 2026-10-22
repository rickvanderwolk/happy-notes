<x-app-layout>
    <x-slot name="title">Search</x-slot>
    @push('head')
        <meta name="navigate-cache-control" content="no-cache">
    @endpush

    <div x-data
         @apply-filter.window="Livewire.navigate('{{ route('notes.show') }}')">
        <div class="text-center mb-4">
            <h2 class="section-title">Search 🔍</h2>
            <p class="section-description-text mt-2">Find notes containing specific text</p>
        </div>

        <livewire:text-filter />
    </div>
</x-app-layout>
