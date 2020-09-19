<?php


namespace Laratrust\Checkers\PermissionAble;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laratrust\Helper;

trait ModelHasPermissionsDefaultCheckerTrait
{
    /**
     * @inheritDoc
     */
    public function currentModelHasPermission($permission, $team = null, $requireAll = false, callable $callback = null)
    {
        $permission = Helper::standardize($permission);
        list($team, $requireAll) = Helper::assignRealValuesTo($team, $requireAll, 'is_bool');

        if (is_array($permission)) {
            if (empty($permission)) {
                return true;
            }

            foreach ($permission as $permissionName) {
                $hasPermission = $this->currentModelHasPermission($permissionName, $team);

                if ($hasPermission && !$requireAll) {
                    return true;
                } elseif (!$hasPermission && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the permissions were found.
            // If we've made it this far and $requireAll is TRUE, then ALL of the permissions were found.
            // Return the value of $requireAll.
            return $requireAll;
        }

        $team = Helper::fetchTeam($team);

        foreach ($this->currentModelCachedPermissions() as $perm) {
            if (Helper::isInSameTeam($perm, $team) && Str::is($permission, $perm['name'])) {
                return true;
            }
        }
        if ($callback) {
            return $callback($permission, $team, $requireAll);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function currentModelFlushCache()
    {
        Cache::forget($this->getModelPermissionsCacheKey());
    }

    /**
     * Tries to return all the cached permissions of the role.
     * If it can't bring the permissions from the cache,
     * it brings them back from the DB.
     *
     * @return Collection
     */
    public function currentModelCachedPermissions()
    {
        $cacheKey = $this->getModelPermissionsCacheKey();

        if (!Config::get('laratrust.cache.enabled')) {
            return $this->model->permissions()->get();
        }

        return Cache::remember($cacheKey, Config::get('laratrust.cache.expiration_time', 60), function () {
            return $this->model->permissions()->get()->toArray();
        });
    }

    /**
     * @return string
     */
    protected function getModelPermissionsCacheKey()
    {
        return $this->model->permissionsCacheKey();
    }

}
