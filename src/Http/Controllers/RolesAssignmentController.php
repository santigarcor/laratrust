<?php

declare(strict_types=1);

namespace Laratrust\Http\Controllers;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Models\Team;
use Laratrust\Panel\RoleRestrictions;
use Laratrust\Panel\SubmittedKeys;
use Laratrust\Support\ModelClasses;

/**
 * Assigns roles and permissions to the users. When teams are enabled, the page shows
 * and saves the assignments of one team at a time (or the ones without a team).
 */
class RolesAssignmentController
{
    public function index(Request $request): ViewContract
    {
        $models = ModelClasses::userRelationships();
        $modelKey = $request->string('model', $models[0] ?? '')->toString();

        abort_unless(in_array($modelKey, $models, true), 404);

        return View::make('laratrust::panel.roles-assignment.index', [
            'models'   => $models,
            'modelKey' => $modelKey,
            'users'    => ModelClasses::user($modelKey)::query()
                ->withCount(['roles', 'permissions'])
                ->simplePaginate(10),
        ]);
    }

    public function edit(Request $request, string $modelId): ViewContract|RedirectResponse
    {
        $modelKey = $request->string('model')->toString();

        if (!in_array($modelKey, ModelClasses::userRelationships(), true)) {
            Session::flash('laratrust-error', 'Model was not specified in the request');

            return to_route('laratrust.roles-assignment.index');
        }

        $team = $this->team($request);
        $user = ModelClasses::user($modelKey)::query()->findOrFail($modelId);
        $assignedRoles = $this->assignedIn($user->roles(), $team)->modelKeys();
        $assignedPermissions = $this->assignedIn($user->permissions(), $team)->modelKeys();

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

        $teams = null;
        if (Config::get('laratrust.teams.enabled')) {
            $teamPrototype = new (ModelClasses::team());
            $teams = $teamPrototype->newQuery()->orderBy('name')->get([$teamPrototype->getKeyName(), 'name', 'display_name']);
        }

        return View::make('laratrust::panel.roles-assignment.edit', [
            'modelKey'    => $modelKey,
            'roles'       => $roles,
            'permissions' => $permissions,
            'teams'       => $teams,
            'team'        => $team,
            'user'        => $user,
        ]);
    }

    public function update(Request $request, string $modelId): RedirectResponse
    {
        $modelKey = $request->string('model')->toString();

        if (!in_array($modelKey, ModelClasses::userRelationships(), true)) {
            Session::flash('laratrust-error', 'Model was not specified in the request');

            return back();
        }

        $team = $this->team($request);
        $data = $request->validate([
            ...SubmittedKeys::rules('roles', ModelClasses::role()),
            ...SubmittedKeys::rules('permissions', ModelClasses::permission()),
        ]);

        $user = ModelClasses::user($modelKey)::query()->findOrFail($modelId);

        // The roles that can't be removed are kept, whatever the request says.
        $roles = array_unique([...$data['roles'] ?? [], ...$this->nonRemovableRoles($user, $team)]);
        $user->syncRoles(SubmittedKeys::assignable($roles), $team);

        if (Config::get('laratrust.panel.assign_permissions_to_user')) {
            $user->syncPermissions(SubmittedKeys::assignable($data['permissions'] ?? []), $team);
        }

        Session::flash('laratrust-success', 'Roles and permissions assigned successfully');

        return to_route('laratrust.roles-assignment.index', ['model' => $modelKey]);
    }

    /**
     * The team chosen in the request, or null for the assignments without a team.
     */
    private function team(Request $request): ?Model
    {
        if (!Config::get('laratrust.teams.enabled') || !$request->filled('team')) {
            return null;
        }

        return ModelClasses::team()::query()->findOrFail($request->string('team')->toString());
    }

    /**
     * The user's roles or permissions, only the ones assigned in the team when teams are enabled.
     *
     * @param  MorphToMany<covariant Model, covariant Model>  $relation
     *
     * @return Collection<int, Model>
     */
    private function assignedIn(MorphToMany $relation, ?Model $team): Collection
    {
        if (Config::get('laratrust.teams.enabled')) {
            $relation->wherePivot(Team::modelForeignKey(), $team?->getKey());
        }

        $related = $relation->getRelated();

        return $relation->get([$related->getQualifiedKeyName(), $related->qualifyColumn('name')]);
    }

    /**
     * @return array<int, int|string>
     */
    private function nonRemovableRoles(Model&LaratrustUser $user, ?Model $team): array
    {
        return $this->assignedIn($user->roles(), $team)
            ->reject(fn (Model $role): bool => RoleRestrictions::isRemovable((string) $role->getAttribute('name')))
            ->modelKeys();
    }
}
