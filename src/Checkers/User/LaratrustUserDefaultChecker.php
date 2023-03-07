<?php

namespace Laratrust\Checkers\User;

use Laratrust\Helper;
use Laratrust\Models\Team;
use Illuminate\Support\Str;
use Laratrust\Contracts\Role;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class LaratrustUserDefaultChecker extends LaratrustUserChecker
{
    /**
     * Checks if the user has a role by its name.
     *
     * @param  string|bool   $team      Team name.
     * @return array
     */
    public function getCurrentUserRoles($team = null)
    {
        $roles = collect($this->userCachedRoles());

        if (config('laratrust.teams.enabled') === false) {
            return $roles->pluck('name')->toArray();
        }

        if ($team === null && config('laratrust.teams.strict_check') === false) {
            return $roles->pluck('name')->toArray();
        }

        if ($team === null) {
            return $roles->filter(function ($role) {
                return $role['pivot'][config('laratrust.foreign_keys.team')] === null;
            })->pluck('name')->toArray();
        }

        $teamId = Team::getId($team);

        return $roles->filter(function ($role) use ($teamId) {
            return $role['pivot'][config('laratrust.foreign_keys.team')] == $teamId;
        })->pluck('name')->toArray();
    }

    /**
     * Checks if the user has a role by its name.
     *
     * @param  string|array  $name       Role name or array of role names.
     * @param  string|bool   $team      Team name or requiredAll roles.
     * @param  bool          $requireAll All roles in the array are required.
     * @return bool
     */
    public function currentUserHasRole($name, $team = null, $requireAll = false)
    {
        $name = Helper::standardize($name);
        list($team, $requireAll) = Helper::assignRealValuesTo($team, $requireAll, 'is_bool');

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

        $team = Team::getId($team);

        foreach ($this->userCachedRoles() as $role) {
            if ($role['name'] == $name && $this->isInSameTeam($role, $team)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param  string|array  $permission Permission string or array of permissions.
     * @param  string|bool  $team      Team name or requiredAll roles.
     * @param  bool  $requireAll All roles in the array are required.
     * @return bool
     */
    public function currentUserHasPermission($permission, $team = null, $requireAll = false)
    {
        $permission = Helper::standardize($permission);
        list($team, $requireAll) = Helper::assignRealValuesTo($team, $requireAll, 'is_bool');

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

        $team = Team::getId($team);

        foreach ($this->userCachedPermissions() as $perm) {
            if ($this->isInSameTeam($perm, $team) && Str::is($permission, $perm['name'])) {
                return true;
            }
        }

        foreach ($this->userCachedRoles() as $role) {
            $role = $this->hidrateRole(Config::get('laratrust.models.role'), $role);

            if ($this->isInSameTeam($role, $team) && $role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function currentUserFlushCache()
    {
        Cache::forget('laratrust_roles_for_'.$this->userModelCacheKey() .'_'. $this->user->getKey());
        Cache::forget('laratrust_permissions_for_'.$this->userModelCacheKey() .'_'. $this->user->getKey());
    }

    /**
     * Tries to return all the cached roles of the user.
     * If it can't bring the roles from the cache,
     * it brings them back from the DB.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function userCachedRoles()
    {
        $cacheKey = 'laratrust_roles_for_'.$this->userModelCacheKey() .'_'. $this->user->getKey();

        if (!Config::get('laratrust.cache.enabled')) {
            return $this->user->roles()->get();
        }

        return Cache::remember($cacheKey, Config::get('laratrust.cache.expiration_time', 60), function () {
            return $this->user->roles()->get()->toArray();
        });
    }

    /**
     * Tries to return all the cached permissions of the user
     * and if it can't bring the permissions from the cache,
     * it brings them back from the DB.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function userCachedPermissions()
    {
        $cacheKey = 'laratrust_permissions_for_'.$this->userModelCacheKey() .'_'. $this->user->getKey();

        if (!Config::get('laratrust.cache.enabled')) {
            return $this->user->permissions()->get();
        }

        return Cache::remember($cacheKey, Config::get('laratrust.cache.expiration_time', 60), function () {
            return $this->user->permissions()->get()->toArray();
        });
    }

    /**
     * Tries return key name for user_models
     *
     * @return string|void default key user
     */
    public function userModelCacheKey()
    {
        foreach (Config::get('laratrust.user_models') as $key => $model) {
            if ($this->user instanceof $model) {
                return $key;
            }
        }
    }

    /**
     * Creates a model from an array filled with the class data.
     */
    private function hidrateRole(string $class, Model|array $data):Role
    {
        if ($data instanceof Model) {
            return $data;
        }

        if (!isset($data['pivot'])) {
            throw new \Exception("The 'pivot' attribute in the {$class} is hidden");
        }

        $role = new $class;
        $primaryKey = $role->getKeyName();

        $role->setAttribute($primaryKey, $data[$primaryKey])->setAttribute('name', $data['name']);
        $role->setRelation(
            'pivot',
            MorphPivot::fromRawAttributes($role, $data['pivot'], 'pivot_table')
        );

        return $role;
    }

    /**
    * Check if a role or permission is attach to the user in a same team.
    */
   private function isInSameTeam($rolePermission, ?int $teamId = null): bool
   {
       if (
           !Config::get('laratrust.teams.enabled')
           || (!Config::get('laratrust.teams.strict_check') && !$teamId)
       ) {
           return true;
       }

       return $rolePermission['pivot'][Team::modelForeignKey()] == $teamId;
   }
}
