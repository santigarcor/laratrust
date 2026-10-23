@extends('laratrust::panel.layout')

@section('title', 'Assignments')

@section('content')
    <x-laratrust::card>
        @if (count($models) > 1)
            <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <label for="model" class="text-sm text-gray-600 dark:text-gray-400">User model</label>
                <select id="model" class="lt-input w-auto py-1.5 pr-8" x-data x-on:change="window.location = '?model=' + encodeURIComponent($event.target.value)">
                    @foreach ($models as $model)
                        <option value="{{ $model }}" @selected($model === $modelKey)>{{ \Illuminate\Support\Str::headline($model) }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="lt-th">Name</th>
                        <th class="lt-th">Roles</th>
                        @if (config('laratrust.panel.assign_permissions_to_user'))
                            <th class="lt-th">Permissions</th>
                        @endif
                        <th class="lt-th"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="lt-td">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $user->name ?? $user->email ?? '#'.$user->getKey() }}</span>
                                @isset($user->name, $user->email)
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</span>
                                @endisset
                            </td>
                            <td class="lt-td">
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ $user->roles_count }}</span>
                            </td>
                            @if (config('laratrust.panel.assign_permissions_to_user'))
                                <td class="lt-td">
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ $user->permissions_count }}</span>
                                </td>
                            @endif
                            <td class="lt-td text-right">
                                <a href="{{ route('laratrust.roles-assignment.edit', ['roles_assignment' => $user->getKey(), 'model' => $modelKey]) }}" class="lt-link">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="lt-td py-10 text-center text-gray-500">There are no users yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-laratrust::pagination :paginator="$users->appends(['model' => $modelKey])" />
    </x-laratrust::card>
@endsection
