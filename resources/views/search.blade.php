<x-app-layout>
    <x-slot name="title">Search</x-slot>
    @push('head')
        <meta name="navigate-cache-control" content="no-cache">
    @endpush

    <div class="container"
         x-data
         @apply-filter.window="Livewire.navigate('{{ route('notes.show') }}')">
        <div class="text-center mb-4">
            <h1 class="section-title">Search 🔍</h1>
            <p class="section-description-text mt-2">Find notes containing specific text</p>
        </div>

        <livewire:text-filter />
    </div>
</x-app-layout>
