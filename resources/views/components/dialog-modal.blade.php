@props(['id' => null, 'maxWidth' => null, 'maxHeight' => null])

<x-modal :id="$id" :maxWidth="$maxWidth" :maxHeight="$maxHeight" {{ $attributes }}>
    {{-- Content stacks above footer so absolutely positioned dropdowns (e.g. phone codes) are not covered by the footer --}}
    <div class="px-6 py-4 relative z-30">
        <div class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ $title }}
        </div>

        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            {{ $content }}
        </div>
    </div>

    @if (isset($footer))
    <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 dark:bg-gray-800 text-end relative z-10 shrink-0">
        {{ $footer }}
    </div>
    @endif
</x-modal>