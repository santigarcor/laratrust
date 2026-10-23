{{-- The panel's flash messages: laratrust-success, laratrust-warning and laratrust-error. --}}
@foreach (['success' => 'bg-green-50 text-green-800 ring-green-600/20 dark:bg-green-500/10 dark:text-green-300', 'warning' => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300', 'error' => 'bg-red-50 text-red-800 ring-red-600/20 dark:bg-red-500/10 dark:text-red-300'] as $type => $classes)
    @if (session()->has("laratrust-{$type}"))
        <div
            x-data="{ show: true }"
            x-show="show"
            x-transition.opacity
            role="{{ $type === 'success' ? 'status' : 'alert' }}"
            class="mb-6 flex items-start justify-between gap-4 rounded-lg px-4 py-3 text-sm ring-1 ring-inset {{ $classes }}"
        >
            <p>{{ session("laratrust-{$type}") }}</p>
            <button type="button" x-on:click="show = false" class="opacity-70 hover:opacity-100" aria-label="Dismiss">&times;</button>
        </div>
    @endif
@endforeach
