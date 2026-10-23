@extends('laratrust::panel.layout')

@section('title', $role->display_name ?? $role->name)

@section('actions')
    <x-laratrust::button variant="secondary" :href="route('laratrust.roles.index')">Back</x-laratrust::button>
@endsection

@section('content')
    <x-laratrust::card class="p-4 sm:p-6">
        <p class="mb-6 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-400">This role can't be edited.</p>

        <dl class="grid gap-6 sm:grid-cols-3">
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Name</dt>
                <dd class="mt-1 font-mono text-sm">{{ $role->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Display name</dt>
                <dd class="mt-1 text-sm">{{ $role->display_name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500 dark:text-gray-400">Description</dt>
                <dd class="mt-1 text-sm">{{ $role->description ?? '—' }}</dd>
            </div>
        </dl>

        <h2 class="mt-8 text-sm font-semibold">Permissions</h2>
        <ul class="mt-3 flex flex-wrap gap-2">
            @forelse ($role->permissions as $permission)
                <li class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 ring-1 ring-indigo-600/20 ring-inset dark:bg-indigo-500/10 dark:text-indigo-300">
                    {{ $permission->display_name ?? $permission->name }}
                </li>
            @empty
                <li class="text-sm text-gray-500 dark:text-gray-400">No permissions.</li>
            @endforelse
        </ul>
    </x-laratrust::card>
@endsection
