{{-- Previous/next links for a simple paginator. --}}
@props(['paginator'])

@if ($paginator->hasPages())
    <nav class="flex items-center justify-between border-t border-gray-200 px-4 py-3 dark:border-white/10" aria-label="Pagination">
        <p class="text-sm text-gray-500 dark:text-gray-400">Page {{ $paginator->currentPage() }}</p>
        <div class="flex gap-2">
            @if ($paginator->onFirstPage())
                <x-laratrust::button variant="secondary" disabled>Previous</x-laratrust::button>
            @else
                <x-laratrust::button variant="secondary" :href="$paginator->previousPageUrl()" rel="prev">Previous</x-laratrust::button>
            @endif

            @if ($paginator->hasMorePages())
                <x-laratrust::button variant="secondary" :href="$paginator->nextPageUrl()" rel="next">Next</x-laratrust::button>
            @else
                <x-laratrust::button variant="secondary" disabled>Next</x-laratrust::button>
            @endif
        </div>
    </nav>
@endif
