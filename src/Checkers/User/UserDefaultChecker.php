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

                if ($hasRole && ! $requireAll) {
                    return true;
                } elseif (! $hasRole && $requireAll) {
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

                if ($hasPermission && ! $requireAll) {
                    return true;
                } elseif (! $hasPermission && $requireAll) {
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

        return false;
    }

    public function currentUserFlushCache()
    {
        Cache::forget('laratrust_roles_for_'.$this->userModelCacheKey().'_'.$this->user->getKey());
        Cache::forget('laratrust_permissions_for_'.$this->userModelCacheKey().'_'.$this->user->getKey());
    }

    /**
     * Tries to return all the cached roles of the user.
     * If it can't bring the roles from the cache,
     * it brings them back from the DB.
     */
    protected function userCachedRoles(): array
    {
        $cacheKey = 'laratrust_roles_for_'.$this->userModelCacheKey().'_'.$this->user->getKey();

        if (! Config::get('laratrust.cache.enabled')) {
            return $this->user->roles()->get()->toArray();
        }

        return Cache::remember($cacheKey, Config::get('laratrust.cache.expiration_time', 60), function () {
            return $this->user->roles()->get()->toArray();
        });
    }

    /**
     * Tries to return all the cached permissions of the user
     * and if it can't bring the permissions from the cache,
     * it brings them back from the DB.
     */
    public function userCachedPermissions(): array
    {
        $cacheKey = 'laratrust_permissions_for_'.$this->userModelCacheKey().'_'.$this->user->getKey();

        if (! Config::get('laratrust.cache.enabled')) {
            return $this->user->permissions()->get()->toArray();
        }

        return Cache::remember($cacheKey, Config::get('laratrust.cache.expiration_time', 60), function () {
            return $this->user->permissions()->get()->toArray();
        });
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

        if (! isset($data['pivot'])) {
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
     * Check if a role or permission is added to the user in a same team.
     */
    private function isInSameTeam($rolePermission, int|string $teamId = null): bool
    {
        if (
            ! Config::get('laratrust.teams.enabled')
            || (! Config::get('laratrust.teams.strict_check') && ! $teamId)
        ) {
            return true;
        }

        return $rolePermission['pivot'][Team::modelForeignKey()] == $teamId;
    }
}
