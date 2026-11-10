@extends('laratrust::panel.layout')

@section('title', ($model ? 'Edit ' : 'New ').$type)

@section('content')
    <x-laratrust::card>
        <form
            method="POST"
            action="{{ $model ? route("laratrust.{$type}s.update", $model->getKey()) : route("laratrust.{$type}s.store") }}"
            x-data="laratrustNameFromDisplayName(@js(old('name', $model?->name ?? '')), @js(old('display_name', $model?->display_name ?? '')), @js($model === null))"
            class="space-y-6 p-4 sm:p-6"
        >
            @csrf
            @if ($model)
                @method('PUT')
            @endif

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="display_name" class="lt-label">Display name</label>
                    <input id="display_name" name="display_name" x-model="displayName" placeholder="Edit user profile" autocomplete="off" class="lt-input mt-2">
                    @error('display_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="name" class="lt-label">Name</label>
                    <input id="name" name="name" x-model="name" readonly placeholder="edit-user-profile" autocomplete="off" aria-describedby="name-help" class="lt-input mt-2 bg-gray-50 font-mono text-gray-600 dark:bg-white/10 dark:text-gray-300">
                    <p id="name-help" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ $model ? "The name can't be changed." : 'Generated from the display name. Use it in your code.' }}
                    </p>
                    @error('name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="description" class="lt-label">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="What this {{ $type }} is for" class="lt-input mt-2">{{ old('description', $model?->description) }}</textarea>
                @error('description') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($type === 'role')
                @php
                    $selected = collect(old('permissions', $permissions->filter->assigned->modelKeys()))->map(fn ($key) => (string) $key)->all();
                @endphp
                <x-laratrust::checklist
                    name="permissions"
                    title="Permissions"
                    :options="$permissions->map(fn ($permission) => [
                        'value' => $permission->getKey(),
                        'label' => $permission->display_name ?? $permission->name,
                        'hint' => $permission->name,
                        'checked' => in_array((string) $permission->getKey(), $selected, true),
                        'locked' => false,
                    ])->all()"
                />
            @endif

            <div class="flex justify-end gap-3 border-t border-gray-200 pt-6 dark:border-white/10">
                <x-laratrust::button variant="secondary" :href="route('laratrust.'.$type.'s.index')">Cancel</x-laratrust::button>
                <x-laratrust::button type="submit">Save</x-laratrust::button>
            </div>
        </form>
    </x-laratrust::card>
@endsection
