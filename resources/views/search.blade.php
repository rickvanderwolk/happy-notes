<x-app-layout>
    @push('head')
        <meta name="turbolinks-cache-control" content="no-cache">
    @endpush

    <div x-data
         @apply-filter.window="window.Turbolinks.visit('{{ route('notes.show') }}')">
        <div class="text-center mb-4">
            <h2 class="section-title">Search Notes 🔍</h2>
            <p class="section-description-text mt-2">Find notes by searching their content</p>
        </div>

        <livewire:text-filter />
    </div>
</x-app-layout>
