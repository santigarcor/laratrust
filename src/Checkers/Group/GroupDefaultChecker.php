<?php

declare(strict_types=1);

namespace Laratrust\Checkers\Group;

use BackedEnum;
use Illuminate\Support\Str;
use Laratrust\Helper;

class GroupDefaultChecker extends GroupChecker
{
    /**
     * Checks if the group has a permission by its name.
     */
    public function currentGroupHasPermission(string|array|BackedEnum $permission, bool $requireAll = false): bool
    {
        if (is_array($permission)) {
            if (empty($permission)) {
                return true;
            }

            foreach ($permission as $permissionName) {
                $hasPermission = $this->currentGroupHasPermission($permissionName);

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

        $permissions = $this->group->permissions()->get()->toArray();

        foreach ($permissions as $perm) {
            if (Str::is(Helper::ensureString($permission), $perm['name'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the group has a permission by its name.
     */
    public function currentGroupHasRole(string|array|BackedEnum $role, bool $requireAll = false): bool
    {

        if (is_array($role)) {
            if (empty($role)) {
                return true;
            }

            foreach ($role as $roleName) {
                $hasRole = $this->currentGroupHasRole($roleName);

                if ($hasRole && !$requireAll) {
                    return true;
                } elseif (!$hasRole && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the permissions were found.
            // If we've made it this far and $requireAll is TRUE, then ALL of the permissions were found.
            // Return the value of $requireAll.
            return $requireAll;
        }

        $roles = $this->group->roles()->get()->toArray();

        foreach ($roles as $rl) {
            if (Str::is(Helper::ensureString($role), $rl['name'])) {
                return true;
            }
        }

        return false;
    }

    public function currentGroupFlushCache(): void
    {
        $users = $this->group->users()->get();
        foreach ($users as $user) {
            $user->flushCache();
        }
    }
}
