<?php

declare(strict_types=1);

namespace Laratrust\Checkers\User;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laratrust\Contracts\Role;
use Laratrust\Contracts\Group;
use Laratrust\Helper;
use Laratrust\Models\Team;
use UnexpectedValueException;

class UserDefaultChecker extends UserChecker
{
    public function getCurrentUserRoles(mixed $team = null): array
    {
        $roles = Collection::make($this->userCachedRoles());

        if (
            Config::get('laratrust.teams.enabled') === false ||
            ($team === null && Config::get('laratrust.teams.strict_check') === false)
        ) {
            return $roles->pluck('name')->toArray();
        }

        if ($team === null) {
            return $roles->filter(function ($role) {
                return $role['pivot'][Team::modelForeignKey()] === null;
            })->pluck('name')->toArray();
        }

        $teamId = Helper::getIdFor($team, 'team');

        return $roles
            ->filter(fn ($role) => $role['pivot'][Team::modelForeignKey()] == $teamId)
            ->pluck('name')
            ->toArray();
    }

    public function getCurrentUserGroups(mixed $team = null): array
    {
        $groups = Collection::make($this->userCachedGroups());

        if (
            Config::get('laratrust.teams.enabled') === false ||
            ($team === null && Config::get('laratrust.teams.strict_check') === false)
        ) {
            return $groups->pluck('name')->toArray();
        }

        if ($team === null) {
            return $groups->filter(function ($group) {
                return $group['pivot'][Team::modelForeignKey()] === null;
            })->pluck('name')->toArray();
        }

        $teamId = Helper::getIdFor($team, 'team');

        return $groups
            ->filter(fn ($group) => $group['pivot'][Team::modelForeignKey()] == $teamId)
            ->pluck('name')
            ->toArray();
    }

    public function getCurrentUserPermissions(mixed $team = null): array
    {
        $permissions = Collection::make($this->userCachedPermissions());

        if (
            Config::get('laratrust.teams.enabled') === false ||
            ($team === null && Config::get('laratrust.teams.strict_check') === false)
        ) {
            return $permissions->pluck('name')->toArray();
        }

        if ($team === null) {
            return $permissions->filter(function ($permission) {
                return $permission['pivot'][Team::modelForeignKey()] === null;
            })->pluck('name')->toArray();
        }

        $teamId = Helper::getIdFor($team, 'team');

        return $permissions
            ->filter(fn ($permission) => $permission['pivot'][Team::modelForeignKey()] == $teamId)
            ->pluck('name')
            ->toArray();
    }

    public function currentUserHasRole(
        string|array|BackedEnum $name,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        $name = Helper::standardize($name);
        [
            'team' => $team,
            'require_all' => $requireAll
        ] = $this->getRealValues($team, $requireAll, 'is_bool');

        if (is_array($name)) {
            if (empty($name)) {
                return true;
            }

            foreach ($name as $roleName) {
                $hasRole = $this->currentUserHasRole($roleName, $team);

                if ($hasRole && !$requireAll) {
                    return true;
                } elseif (!$hasRole && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the roles were found.
            // If we've made it this far and $requireAll is TRUE, then ALL of the roles were found.
            // Return the value of $requireAll.
            return $requireAll;
        }

        $teamId = Helper::getIdFor($team, 'team');

        foreach ($this->userCachedRoles() as $role) {
            if ($role['name'] == $name && $this->isInSameTeam($role, $teamId)) {
                return true;
            }
        }

        return false;
    }

    public function currentUserHasGroup(
        string|array|BackedEnum $name,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        $name = Helper::standardize($name);
        [
            'team' => $team,
            'require_all' => $requireAll
        ] = $this->getRealValues($team, $requireAll, 'is_bool');

        if (is_array($name)) {
            if (empty($name)) {
                return true;
            }

            foreach ($name as $grouName) {
                $hasGroup = $this->currentUserHasGroup($grouName, $team);

                if ($hasGroup && !$requireAll) {
                    return true;
                } elseif (!$hasGroup && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the groups were found.
            // If we've made it this far and $requireAll is TRUE, then ALL of the groups were found.
            // Return the value of $requireAll.
            return $requireAll;
        }

        $teamId = Helper::getIdFor($team, 'team');

        foreach ($this->userCachedGroups() as $group) {
            if ($group['name'] == $name && $this->isInSameTeam($group, $teamId)) {
                return true;
            }
        }

        return false;
    }

    public function currentUserHasPermission(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        $permission = Helper::standardize($permission);
        [
            'team' => $team,
            'require_all' => $requireAll
        ] = $this->getRealValues($team, $requireAll, 'is_bool');

        if (is_array($permission)) {
            if (empty($permission)) {
                return true;
            }

            foreach ($permission as $permissionName) {
                $hasPermission = $this->currentUserHasPermission($permissionName, $team);

                if ($hasPermission && !$requireAll) {
                    return true;
                } elseif (!$hasPermission && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the perms were found.
            // If we've made it this far and $requireAll is TRUE, then ALL of the perms were found.
            // Return the value of $requireAll.
            return $requireAll;
        }

        $teamId = Helper::getIdFor($team, 'team');

        foreach ($this->userCachedPermissions() as $perm) {
            if ($this->isInSameTeam($perm, $teamId) && Str::is($permission, $perm['name'])) {
                return true;
            }
        }

        foreach ($this->userCachedRoles() as $role) {
            $role = $this->hidrateRole(Config::get('laratrust.models.role'), $role);

            if ($this->isInSameTeam($role, $teamId) && $role->hasPermission($permission)) {
                return true;
            }
        }

        foreach ($this->userCachedGroups() as $group) {
            $group = $this->hidrateGroup(Config::get('laratrust.models.group'), $group);

            if ($this->isInSameTeam($group, $teamId) && $group->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function currentUserFlushCache(bool $recreate = true)
    {
        Cache::forget('laratrust_cache_for_' . $this->user->getKey());
        if ($recreate) {
            $this->resolvePermissions();
        }
    }

    /**
     * Tries to return all the cached roles of the user.
     * If it can't bring the roles from the cache,
     * it brings them back from the DB.
     */
    protected function userCachedRoles(): array
    {
        $data = $this->resolvePermissions();
        return $data["roles"];
    }

    /**
     * Tries to return all the cached groups of the user.
     * If it can't bring the groups from the cache,
     * it brings them back from the DB.
     */
    protected function userCachedGroups(): array
    {
        $data = $this->resolvePermissions();
        return $data["groups"];
    }

    /**
     * Tries to return all the cached permissions of the user
     * and if it can't bring the permissions from the cache,
     * it brings them back from the DB.
     */
    public function userCachedPermissions(): array
    {
        $data = $this->resolvePermissions();
        return $data["permissions"];
    }

    /**
     * Tries return key name for user_models.
     *
     * @return string|void default key user
     */
    public function userModelCacheKey(): string
    {
        foreach (Config::get('laratrust.user_models') as $key => $model) {
            if ($this->user instanceof $model) {
                return $key;
            }
        }

        $modelClass = get_class($this);

        throw new UnexpectedValueException("Class '{$modelClass}' is not defined in the laratrust.user_models");
    }

    /**
     * Creates a model from an array filled with the class data.
     */
    private function hidrateRole(string $class, Model|array $data): Role
    {
        if ($data instanceof Model) {
            return $data;
        }

        if (!isset($data['pivot'])) {
            throw new \Exception("The 'pivot' attribute in the {$class} is hidden");
        }

        $role = new $class;
        $primaryKey = $role->getKeyName();

        $role
            ->setAttribute($primaryKey, $data[$primaryKey])
            ->setAttribute('name', $data['name'])
            ->setRelation(
                'pivot',
                MorphPivot::fromRawAttributes($role, $data['pivot'], 'pivot_table')
            );

        return $role;
    }

    /**
     * Creates a model from an array filled with the class data.
     */
    private function hidrateGroup(string $class, Model|array $data): Group
    {
        if ($data instanceof Model) {
            return $data;
        }

        if (!isset($data['pivot'])) {
            throw new \Exception("The 'pivot' attribute in the {$class} is hidden");
        }

        $group = new $class;
        $primaryKey = $group->getKeyName();

        $group
            ->setAttribute($primaryKey, $data[$primaryKey])
            ->setAttribute('name', $data['name'])
            ->setRelation(
                'pivot',
                MorphPivot::fromRawAttributes($group, $data['pivot'], 'pivot_table')
            );

        return $group;
    }

    /**
     * Check if a role or permission is added to the user in a same team.
     */
    private function isInSameTeam($groupRolePermission, int|string $teamId = null): bool
    {
        if (
            !Config::get('laratrust.teams.enabled')
            || (!Config::get('laratrust.teams.strict_check') && !$teamId)
        ) {
            return true;
        }

        return $groupRolePermission['pivot'][Team::modelForeignKey()] == $teamId;
    }

    public function resolvePermissions(): array
    {
        $cacheKey = 'laratrust_cache_for_' . $this->user->getKey();

        if (!Config::get('laratrust.cache.enabled')) {
            return $this->permissionResolver();
        };

        return Cache::remember($cacheKey, Config::get('laratrust.cache.expiration_time', 60), function () {
            return $this->permissionResolver();
        });
    }

    private function permissionResolver(): array
    {
        $groups = $this->user->groups()->get();
        $roles = $this->user->roles()->get();
        $permissions = new Collection();

        foreach ($groups as $group) {
            $roles = $roles->merge($group->roles()->get());
        }
        foreach ($roles as $role) {
            $permissions = $permissions->merge($role->permissions()->get());
        }

        return [
            'groups' => array_map(fn ($group) => [
                'id' => $group['id'],
                'name' => $group['name'],
                'display_name' => $group['display_name'],
            ], $groups->unique('id')->toArray()),
            'roles' => array_map(fn ($role) => [
                'id' => $role['id'],
                'name' => $role['name'],
                'display_name' => $role['display_name'],
            ], $roles->unique('id')->toArray()),
            'permissions' => array_map(fn ($permission) => [
                'id' => $permission['id'],
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
            ], $permissions->unique('id')->toArray())
        ];
    }
}
