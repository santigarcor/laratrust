<?php

declare(strict_types=1);

namespace Laratrust\Checkers\Role;

use BackedEnum;
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

        $permissions = $this->role->permissions()->get()->toArray();

        foreach ($permissions as $perm) {
            if (Str::is(Helper::ensureString($permission), $perm['name'])) {
                return true;
            }
        }

        return false;
    }

    public function currentRoleFlushCache(): void
    {
        $users = $this->role->users()->get();
        $groups = $this->role->groups()->get();

        foreach ($groups as $group) {
            $users = $users->merge($group->users()->get());
        }

        foreach ($users as $user) {
            $user->flushCache();
        }
    }
}
