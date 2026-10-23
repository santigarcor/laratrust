@extends('laratrust::panel.layout')

@section('title', 'Permissions')

@section('actions')
    @if (config('laratrust.panel.create_permissions'))
        <x-laratrust::button :href="route('laratrust.permissions.create')">New permission</x-laratrust::button>
    @endif
@endsection

@section('content')
    <x-laratrust::card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="lt-th">Display name</th>
                        <th class="lt-th hidden sm:table-cell">Name</th>
                        <th class="lt-th hidden lg:table-cell">Description</th>
                        <th class="lt-th"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($permissions as $permission)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="lt-td font-medium text-gray-900 dark:text-white">{{ $permission->display_name ?? '—' }}</td>
                            <td class="lt-td hidden font-mono text-xs sm:table-cell">{{ $permission->name }}</td>
                            <td class="lt-td hidden max-w-sm truncate lg:table-cell">{{ $permission->description }}</td>
                            <td class="lt-td text-right">
                                <a href="{{ route('laratrust.permissions.edit', $permission->getKey()) }}" class="lt-link">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="lt-td py-10 text-center text-gray-500">There are no permissions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-laratrust::pagination :paginator="$permissions" />
    </x-laratrust::card>
@endsection
