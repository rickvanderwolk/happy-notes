@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="px-6 py-4">
        <div class="text-lg font-medium section-title-text">
            {{ $title }}
        </div>

        <div class="mt-4 text-sm section-description-text">
            {{ $content }}
        </div>
    </div>

    <div class="flex flex-row justify-end px-6 py-4 account-card text-end">
        {{ $footer }}
    </div>
</x-modal>
