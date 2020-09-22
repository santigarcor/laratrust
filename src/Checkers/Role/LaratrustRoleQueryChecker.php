<?php

namespace Laratrust\Checkers\Role;

use Laratrust\Helper;
use Illuminate\Support\Facades\Cache;

class LaratrustRoleQueryChecker extends LaratrustRoleChecker
{
    /**
     * @inheritDoc
     */
    public function currentRoleHasPermission($permission, $requireAll = false)
    {
        if (empty($permission)) {
            return true;
        }

        $permission = Helper::standardize($permission);
        $permissionsNames = is_array($permission) ? $permission : [$permission];

        list($permissionsWildcard, $permissionsNoWildcard) =
            Helper::getPermissionWithAndWithoutWildcards($permissionsNames);

        $permissionsCount = $this->role->permissions()
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

    /**
     * @inheritDoc
     */
    public function currentRoleFlushCache()
    {
    }
}
