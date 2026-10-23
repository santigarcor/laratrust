<?php

declare(strict_types=1);

namespace Laratrust\Http\Controllers;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Laratrust\Panel\RoleRestrictions;
use Laratrust\Support\ModelClasses;

class RolesAssignmentController
{
    public function index(Request $request): ViewContract
    {
        $models = ModelClasses::userRelationships();
        $modelKey = $request->string('model', $models[0] ?? '')->toString();

        abort_unless(in_array($modelKey, $models, true), 404);

        return View::make('laratrust::panel.roles-assignment.index', [
            'models' => $models,
            'modelKey' => $modelKey,
            'users' => ModelClasses::user($modelKey)::query()
                ->withCount(['roles', 'permissions'])
                ->simplePaginate(10),
        ]);
    }

    public function edit(Request $request, string $modelId): ViewContract|RedirectResponse
    {
        $modelKey = $request->string('model')->toString();

        if (! in_array($modelKey, ModelClasses::userRelationships(), true)) {
            Session::flash('laratrust-error', 'Model was not specified in the request');

            return to_route('laratrust.roles-assignment.index');
        }

        $user = ModelClasses::user($modelKey)::query()->with(['roles', 'permissions'])->findOrFail($modelId);
        $assignedRoles = $user->getRelation('roles')->modelKeys();
        $assignedPermissions = $user->getRelation('permissions')->modelKeys();

        $role = new (ModelClasses::role());
        $roles = $role->newQuery()
            ->orderBy('name')
            ->get([$role->getKeyName(), 'name', 'display_name'])
            ->each(fn (Model $role) => $role
                ->setAttribute('assigned', in_array($role->getKey(), $assignedRoles, true))
                ->setAttribute('isRemovable', RoleRestrictions::isRemovable((string) $role->getAttribute('name'))));

        $permissions = null;
        if (Config::get('laratrust.panel.assign_permissions_to_user')) {
            $permission = new (ModelClasses::permission());
            $permissions = $permission->newQuery()
                ->orderBy('name')
                ->get([$permission->getKeyName(), 'name', 'display_name'])
                ->each(fn (Model $permission) => $permission->setAttribute('assigned', in_array($permission->getKey(), $assignedPermissions, true)));
        }

        return View::make('laratrust::panel.roles-assignment.edit', [
            'modelKey' => $modelKey,
            'roles' => $roles,
            'permissions' => $permissions,
            'user' => $user,
        ]);
    }

    public function update(Request $request, string $modelId): RedirectResponse
    {
        $modelKey = $request->string('model')->toString();

        if (! in_array($modelKey, ModelClasses::userRelationships(), true)) {
            Session::flash('laratrust-error', 'Model was not specified in the request');

            return back();
        }

        $user = ModelClasses::user($modelKey)::query()->findOrFail($modelId);
        $user->syncRoles($request->input('roles', []));

        if (Config::get('laratrust.panel.assign_permissions_to_user')) {
            $user->syncPermissions($request->input('permissions', []));
        }

        Session::flash('laratrust-success', 'Roles and permissions assigned successfully');

        return to_route('laratrust.roles-assignment.index', ['model' => $modelKey]);
    }
}
