{{--
    A filterable list of checkboxes named "{name}[]".
    Each option: value, label, hint (shown under the label), checked, and locked (checked and can't be changed).
--}}
@props(['name', 'title', 'options'])

<fieldset x-data="laratrustChecklist" {{ $attributes }}>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <legend class="lt-label">
            {{ $title }}
            <span class="ml-1 font-normal text-gray-500 dark:text-gray-400" x-text="`(${selected} selected)`"></span>
        </legend>

        @if (count($options) > 0)
            <div class="flex w-full items-center gap-2 sm:w-auto">
                <input type="search" x-model="search" placeholder="Filter…" aria-label="Filter {{ strtolower($title) }}" class="lt-input min-w-0 flex-1 py-1.5 sm:w-44 sm:flex-none">
                <x-laratrust::button variant="secondary" class="py-1.5" x-on:click="selectAll">All</x-laratrust::button>
                <x-laratrust::button variant="secondary" class="py-1.5" x-on:click="selectNone">None</x-laratrust::button>
            </div>
        @endif
    </div>

    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($options as $option)
            <label
                x-show="matches(@js(mb_strtolower($option['label'].' '.$option['hint'])))"
                @class([
                    'flex items-start gap-3 rounded-lg p-3 ring-1 ring-gray-200 transition has-checked:bg-indigo-50 has-checked:ring-indigo-300 dark:ring-white/10 dark:has-checked:bg-indigo-500/10 dark:has-checked:ring-indigo-500/40',
                    'cursor-pointer hover:bg-gray-50 dark:hover:bg-white/5' => ! $option['locked'],
                    'cursor-not-allowed opacity-75' => $option['locked'],
                ])
            >
                @if ($option['locked'])
                    {{-- A disabled checkbox isn't submitted, so the value goes in a hidden input. --}}
                    <input type="hidden" name="{{ $name }}[]" value="{{ $option['value'] }}">
                    <input type="checkbox" checked disabled data-locked class="mt-0.5 size-4 rounded border-gray-300 text-gray-400">
                @else
                    <input type="checkbox" name="{{ $name }}[]" value="{{ $option['value'] }}" @checked($option['checked']) class="mt-0.5 size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 dark:border-white/20 dark:bg-white/5">
                @endif
                <span class="min-w-0">
                    <span class="block truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $option['label'] }}</span>
                    @if ($option['hint'] !== $option['label'])
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $option['hint'] }}</span>
                    @endif
                    @if ($option['locked'])
                        <span class="mt-1 inline-block text-xs text-gray-500 dark:text-gray-400">Can't be removed</span>
                    @endif
                </span>
            </label>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400 sm:col-span-2 lg:col-span-3">Nothing to choose from yet.</p>
        @endforelse
    </div>
</fieldset>
