<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50 dark:bg-gray-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title') · Laratrust</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='1' x2='0' y2='0'%3E%3Cstop offset='0' stop-color='%2336d1dc'/%3E%3Cstop offset='1' stop-color='%235b86e5'/%3E%3C/linearGradient%3E%3C/defs%3E%3Ccircle cx='40' cy='40' r='40' fill='url(%23g)'/%3E%3C/svg%3E">
    {{ \Laratrust\Panel\Assets::css() }}
</head>
<body class="h-full font-sans text-gray-900 antialiased dark:text-gray-100">
    @php
        $links = [
            ['route' => 'laratrust.roles-assignment.index', 'pattern' => '*roles-assignment*', 'label' => 'Assignments'],
            ['route' => 'laratrust.roles.index', 'pattern' => '*roles', 'label' => 'Roles'],
            ['route' => 'laratrust.permissions.index', 'pattern' => '*permissions*', 'label' => 'Permissions'],
        ];
    @endphp

    <header x-data="{ open: false }" class="border-b border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-6 px-4 sm:px-6">
            <a href="{{ route('laratrust.roles-assignment.index') }}" class="text-gray-900 dark:text-white" aria-label="Laratrust">
                <x-laratrust::logo />
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Main">
                @foreach ($links as $link)
                    <a
                        href="{{ route($link['route']) }}"
                        @class([
                            'rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white' => request()->is($link['pattern']),
                            'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-white' => ! request()->is($link['pattern']),
                        ])
                        @if (request()->is($link['pattern'])) aria-current="page" @endif
                    >{{ $link['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <a href="{{ config('laratrust.panel.go_back_route') }}" class="hidden text-sm font-medium text-gray-600 hover:text-gray-900 md:block dark:text-gray-400 dark:hover:text-white">&larr; Back to the app</a>
                <button type="button" class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 md:hidden dark:text-gray-400 dark:hover:bg-white/10" x-on:click="open = ! open" :aria-expanded="open" aria-label="Menu">
                    <svg x-show="! open" class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    <svg x-show="open" x-cloak class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </div>

        <nav x-show="open" x-cloak x-transition class="space-y-1 border-t border-gray-200 px-4 py-3 md:hidden dark:border-white/10" aria-label="Main">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" @class([
                    'block rounded-lg px-3 py-2 text-base font-medium',
                    'bg-gray-100 text-gray-900 dark:bg-white/10 dark:text-white' => request()->is($link['pattern']),
                    'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/5' => ! request()->is($link['pattern']),
                ])>{{ $link['label'] }}</a>
            @endforeach
            <a href="{{ config('laratrust.panel.go_back_route') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-white/5">&larr; Back to the app</a>
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold tracking-tight">@yield('title')</h1>
            <div class="flex items-center gap-2">@yield('actions')</div>
        </div>

        <x-laratrust::flash />

        @yield('content')
    </main>

    {{ \Laratrust\Panel\Assets::js() }}
</body>
</html>
