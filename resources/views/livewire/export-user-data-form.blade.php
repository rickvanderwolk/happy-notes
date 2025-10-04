<x-action-section>
    <x-slot name="title">
        {{ __('Export Data') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Download all your notes in CSV or JSON format.') }}
    </x-slot>

    <x-slot name="content">
        <div>
            <a href="{{ route('user.export.notes.create', ['format' => 'csv']) }}" class="btn btn-secondary me-2" data-turbo="false">
                Download CSV
            </a>
            <a href="{{ route('user.export.notes.create', ['format' => 'json']) }}" class="btn btn-secondary" data-turbo="false">
                Download JSON
            </a>
        </div>
    </x-slot>
</x-action-section>
