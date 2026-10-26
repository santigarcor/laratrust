<?php

declare(strict_types=1);

namespace Laratrust\Http\Controllers;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Laratrust\Panel\RoleRestrictions;
use Laratrust\Panel\SubmittedKeys;
use Laratrust\Support\ModelClasses;

class RolesController
{
    public function index(): ViewContract
    {
        return View::make('laratrust::panel.roles.index', [
            'roles' => ModelClasses::role()::query()
                ->withCount('permissions')
                ->simplePaginate(10)
                ->through(fn (Model $role) => $role
                    ->setAttribute('isEditable', RoleRestrictions::isEditable((string) $role->getAttribute('name')))
                    ->setAttribute('isDeletable', RoleRestrictions::isDeletable((string) $role->getAttribute('name')))),
        ]);
    }

    public function create(): ViewContract
    {
        $permission = new (ModelClasses::permission());

        return View::make('laratrust::panel.edit', [
            'model' => null,
            'permissions' => $permission->newQuery()->orderBy('name')->get([$permission->getKeyName(), 'name', 'display_name']),
            'type' => 'role',
        ]);
    }

    public function show(string $id): ViewContract
    {
        $permission = new (ModelClasses::permission());

        $role = ModelClasses::role()::query()
            ->with(['permissions' => fn ($query) => $query->select($permission->qualifyColumns([$permission->getKeyName(), 'name', 'display_name']))])
            ->findOrFail($id);

        return View::make('laratrust::panel.roles.show', ['role' => $role]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', Rule::unique(ModelClasses::role(), 'name')],
            'display_name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            ...SubmittedKeys::rules('permissions', ModelClasses::permission()),
        ]);

        ModelClasses::role()::query()
            ->create(Arr::except($data, 'permissions'))
            ->syncPermissions(SubmittedKeys::assignable($data['permissions'] ?? []));

        Session::flash('laratrust-success', 'Role created successfully');

        return to_route('laratrust.roles.index');
    }

    public function edit(string $id): ViewContract|RedirectResponse
    {
        $role = ModelClasses::role()::query()->with('permissions')->findOrFail($id);

        if (! RoleRestrictions::isEditable($role)) {
            Session::flash('laratrust-error', 'The role is not editable');

            return back();
        }

        $assigned = $role->getRelation('permissions')->modelKeys();
        $permission = new (ModelClasses::permission());
        $permissions = $permission->newQuery()
            ->orderBy('name')
            ->get([$permission->getKeyName(), 'name', 'display_name'])
            ->each(fn (Model $permission) => $permission->setAttribute('assigned', in_array($permission->getKey(), $assigned, true)));

        return View::make('laratrust::panel.edit', [
            'model' => $role,
            'permissions' => $permissions,
            'type' => 'role',
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $role = ModelClasses::role()::query()->findOrFail($id);

        if (! RoleRestrictions::isEditable($role)) {
            Session::flash('laratrust-error', 'The role is not editable');

            return back();
        }

        $data = $request->validate([
            'display_name' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            ...SubmittedKeys::rules('permissions', ModelClasses::permission()),
        ]);

        $role->update(Arr::except($data, 'permissions'));
        $role->syncPermissions(SubmittedKeys::assignable($data['permissions'] ?? []));

        Session::flash('laratrust-success', 'Role updated successfully');

        return to_route('laratrust.roles.index');
    }

    public function destroy(string $id): RedirectResponse
    {
        $role = ModelClasses::role()::query()->findOrFail($id);

        if (! RoleRestrictions::isDeletable($role)) {
            Session::flash('laratrust-error', 'The role is not deletable');

            return back();
        }

        $isAssigned = DB::table(Config::get('laratrust.tables.role_user'))
            ->where(Config::get('laratrust.foreign_keys.role'), $role->getKey())
            ->exists();

        if ($isAssigned) {
            Session::flash('laratrust-warning', 'Role is added to one or more users. It can not be deleted');
        } else {
            $role->delete();
            Session::flash('laratrust-success', 'Role deleted successfully');
        }

        return to_route('laratrust.roles.index');
    }
}
