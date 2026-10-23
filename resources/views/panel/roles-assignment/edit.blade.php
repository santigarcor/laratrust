@extends('laratrust::panel.layout')

@section('title', $user->name ?? $user->email ?? \Illuminate\Support\Str::headline(\Illuminate\Support\Str::singular($modelKey)).' #'.$user->getKey())

@section('content')
    <x-laratrust::card>
        <form method="POST" action="{{ route('laratrust.roles-assignment.update', ['roles_assignment' => $user->getKey(), 'model' => $modelKey]) }}" class="space-y-8 p-4 sm:p-6">
            @csrf
            @method('PUT')

            @php
                $selectedRoles = collect(old('roles', $roles->filter->assigned->modelKeys()))->map(fn ($key) => (string) $key)->all();
            @endphp
            <x-laratrust::checklist
                name="roles"
                title="Roles"
                :options="$roles->map(fn ($role) => [
                    'value' => $role->getKey(),
                    'label' => $role->display_name ?? $role->name,
                    'hint' => $role->name,
                    'checked' => in_array((string) $role->getKey(), $selectedRoles, true),
                    'locked' => $role->assigned && ! $role->isRemovable,
                ])->all()"
            />

            @if ($permissions)
                @php
                    $selectedPermissions = collect(old('permissions', $permissions->filter->assigned->modelKeys()))->map(fn ($key) => (string) $key)->all();
                @endphp
                <x-laratrust::checklist
                    name="permissions"
                    title="Permissions"
                    :options="$permissions->map(fn ($permission) => [
                        'value' => $permission->getKey(),
                        'label' => $permission->display_name ?? $permission->name,
                        'hint' => $permission->name,
                        'checked' => in_array((string) $permission->getKey(), $selectedPermissions, true),
                        'locked' => false,
                    ])->all()"
                />
            @endif

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-white/10">
                <x-laratrust::button variant="secondary" :href="route('laratrust.roles-assignment.index', ['model' => $modelKey])">Cancel</x-laratrust::button>
                <x-laratrust::button type="submit">Save</x-laratrust::button>
            </div>
        </form>
    </x-laratrust::card>
@endsection
