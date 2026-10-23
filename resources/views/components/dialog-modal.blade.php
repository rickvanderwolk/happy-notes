@props(['id' => null, 'maxWidth' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" {{ $attributes }}>
    <div class="py-4">
        <div class="font-medium section-title-text">
            {{ $title }}
        </div>

        <div class="mt-4 section-description-text">
            {{ $content }}
        </div>
    </div>

    <div class="flex-row justify-end py-4 account-card text-end">
        {{ $footer }}
    </div>
</x-modal>
