<?php

declare(strict_types=1);

namespace Laratrust\Checkers\Group;

use BackedEnum;
use Laratrust\Helper;

class GroupQueryChecker extends GroupChecker
{
    /**
     * Checks if the role has a permission by its name.
     */
    public function currentGroupHasPermission(string|array|BackedEnum $permission, bool $requireAll = false): bool
    {
        if (empty($permission)) {
            return true;
        }

        $permission = Helper::standardize($permission);
        $permissionsNames = is_array($permission) ? $permission : [$permission];

        [$permissionsWildcard, $permissionsNoWildcard] =
            Helper::getPermissionWithAndWithoutWildcards($permissionsNames);

        $permissionsCount = $this->group->permissions()
            ->whereIn('name', $permissionsNoWildcard)
            ->when($permissionsWildcard, function ($query) use ($permissionsWildcard) {
                foreach ($permissionsWildcard as $permission) {
                    $query->orWhere('name', 'like', $permission);
                }

                return $query;
            })
            ->count();

        return $requireAll
            ? $permissionsCount >= count($permissionsNames)
            : $permissionsCount > 0;
    }

    public function currentGroupHasRole(string|array|BackedEnum $role, bool $requireAll = false): bool
    {
        if (empty($role)) {
            return true;
        }

        $role = Helper::standardize($role);
        $rolesNames = is_array($role) ? $role : [$role];

        [$rolesWildcard, $rolesNoWildcard] =
            Helper::getPermissionWithAndWithoutWildcards($rolesNames);

        $rolesCount = $this->group->roles()
            ->whereIn('name', $rolesNoWildcard)
            ->when($rolesWildcard, function ($query) use ($rolesWildcard) {
                foreach ($rolesWildcard as $role) {
                    $query->orWhere('name', 'like', $role);
                }

                return $query;
            })
            ->count();

        return $requireAll
            ? $rolesCount >= count($rolesNames)
            : $rolesCount > 0;
    }

    public function currentGroupFlushCache(): void
    {
    }
}
