<?php

namespace Laratrust\Checkers\User;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laratrust\Helper;

class LaratrustUserDefaultChecker extends LaratrustUserChecker
{
    /**
     * @inheritDoc
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

        $teamId = Helper::fetchTeam($team);

        return $roles->filter(function ($role) use ($teamId) {
            return $role['pivot'][config('laratrust.foreign_keys.team')] == $teamId;
        })->pluck('name')->toArray();
    }

    /**
     * @inheritDoc
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

        $team = Helper::fetchTeam($team);

        foreach ($this->userCachedRoles() as $role) {
            if ($role['name'] == $name && Helper::isInSameTeam($role, $team)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
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

        $team = Helper::fetchTeam($team);

        foreach ($this->userCachedPermissions() as $perm) {
            if (Helper::isInSameTeam($perm, $team) && Str::is($permission, $perm['name'])) {
                return true;
            }
        }

        foreach ($this->userCachedRoles() as $role) {
            $role = Helper::hidrateModel(Config::get('laratrust.models.role'), $role);

            if (Helper::isInSameTeam($role, $team) && $role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
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
     * @return string default key user
     */
    public function userModelCacheKey()
    {
        if (!Config::get('laratrust.use_morph_map')) {
            return 'user';
        }

        foreach (Config::get('laratrust.user_models') as $key => $model) {
            if ($this->user instanceof $model) {
                return $key;
            }
        }
    }
}
