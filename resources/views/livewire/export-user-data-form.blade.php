<x-action-section>
    <x-slot name="title">
        {{ __('Export Data') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Download all your notes in CSV or JSON format.') }}
    </x-slot>

    <x-slot name="content">
        <div>
            <form action="{{ route('user.export.notes.create') }}" method="GET" style="display: inline;">
                <input type="hidden" name="format" value="csv">
                <button type="submit" class="btn btn-secondary me-2">
                    Download CSV
                </button>
            </form>
            <form action="{{ route('user.export.notes.create') }}" method="GET" style="display: inline;">
                <input type="hidden" name="format" value="json">
                <button type="submit" class="btn btn-secondary">
                    Download JSON
                </button>
            </form>
        </div>
    </x-slot>
</x-action-section>
