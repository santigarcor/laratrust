<?php

declare(strict_types=1);

namespace Laratrust\Checkers\Role;

use BackedEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laratrust\Helper;

class RoleDefaultChecker extends RoleChecker
{
    /**
     * Checks if the role has a permission by its name.
     */
    public function currentRoleHasPermission(string|array|BackedEnum $permission, bool $requireAll = false): bool
    {
        if (is_array($permission)) {
            if (empty($permission)) {
                return true;
            }

            foreach ($permission as $permissionName) {
                $hasPermission = $this->currentRoleHasPermission($permissionName);

                if ($hasPermission && ! $requireAll) {
                    return true;
                } elseif (! $hasPermission && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the permissions were found.
            // If we've made it this far and $requireAll is TRUE, then ALL of the permissions were found.
            // Return the value of $requireAll.
            return $requireAll;
        }

        foreach ($this->currentRoleCachedPermissions() as $perm) {
            if (Str::is(Helper::ensureString($permission), $perm['name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Flush the role's cache.
     */
    public function currentRoleFlushCache(): void
    {
        Cache::forget('laratrust_permissions_for_role_'.$this->role->getKey());
    }

    /**
     * Tries to return all the cached permissions of the role.
     * If it can't bring the permissions from the cache,
     * it brings them back from the DB.
     */
    public function currentRoleCachedPermissions(): array
    {
        $cacheKey = 'laratrust_permissions_for_role_'.$this->role->getKey();

        if (! Config::get('laratrust.cache.enabled')) {
            return $this->role->permissions()->get()->toArray();
        }

        return Cache::remember($cacheKey, Config::get('laratrust.cache.expiration_time', 60), function () {
            return $this->role->permissions()->get()->toArray();
        });
    }
}
