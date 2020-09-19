<?php

namespace Laratrust\Checkers\User;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Laratrust\Checkers\PermissionAble\ModelHasPermissionsDefaultCheckerTrait;
use Laratrust\Helper;
use Laratrust\Models\LaratrustTeam;
use Laratrust\Traits\LaratrustRoleTrait;

class LaratrustUserDefaultChecker extends LaratrustUserChecker
{
    use ModelHasPermissionsDefaultCheckerTrait {
        currentModelHasPermission as baseModelHasPermission;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentUserRoles($team = null)
    {
        $roles = collect($this->userCachedRelation('roles'));

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

        foreach ($this->userCachedRelation('roles') as $role) {
            if ($role['name'] == $name && Helper::isInSameTeam($role, $team)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function currentModelHasPermission($permission, $team = null, $requireAll = false, callable $callback = null)
    {

        return self::baseModelHasPermission($permission, $team, $requireAll, function ($permission, $team = null, $requireAll = false) {
            foreach ($this->userCachedRelation('roles') as $role) {
                /** @var LaratrustRoleTrait $role */
                $role = Helper::hidrateModel(Config::get('laratrust.models.role'), $role);

                if (Helper::isInSameTeam($role, $team) && $role->hasPermission($permission, $team)) {
                    return true;
                }
            }

            if (Config::get('laratrust.teams.enabled')) {

                foreach ($this->userCachedRelation('rolesTeams') as $team) {
                    /** @var LaratrustTeam $team */
                    $team = Helper::hidrateModel(Config::get('laratrust.models.team'), $team);

                    if (Helper::isInSameTeam($team, $team) && $team->hasPermission($permission, $team)) {
                        return true;
                    }
                }
            }

            return false;
        });

    }

    public function currentUserFlushCache()
    {
        Cache::forget('laratrust_roles_for_'.$this->userModelCacheKey().'_'.$this->model->getKey());
        Cache::forget('laratrust_permissions_for_'.$this->userModelCacheKey().'_'.$this->model->getKey());
    }


    /**
     * Tries to return all the cached roles of the user.
     * If it can't bring the roles from the cache,
     * it brings them back from the DB.
     *
     * @param  string  $relation
     * @return Collection
     */
    protected function userCachedRelation(string $relation)
    {

        $cacheKey = 'laratrust_'.strtolower($relation).'_for_'.$this->userModelCacheKey().'_'.$this->model->getKey();

        if (!Config::get('laratrust.cache.enabled')) {
            return $this->model->{$relation}()->get();
        }

        return Cache::remember($cacheKey, Config::get('laratrust.cache.expiration_time', 60), function () use ($relation) {
            return $this->model->{$relation}()->get()->toArray();
        });
    }


    /**
     * @return string
     */
    protected function getModelPermissionsCacheKey()
    {
        return $cacheKey = 'laratrust_permissions_for_'.$this->userModelCacheKey().'_'.$this->model->getKey();
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
            if ($this->model instanceof $model) {
                return $key;
            }
        }
    }
}
