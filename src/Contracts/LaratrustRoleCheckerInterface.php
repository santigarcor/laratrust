<?php

namespace Laratrust\Contracts;

use Illuminate\Database\Eloquent\Model;

interface LaratrustRoleCheckerInterface
{
    public function __construct(Model $role);

    /**
     * Checks if the role has a permission by its name.
     *
     * @param  string|string[]  $permission       Permission name or array of permission names.
     * @param  bool $requireAll       All permissions in the array are required.
     * @return bool
     */
    public function currentRoleHasPermission($permission, $requireAll = false);

    /**
     * Flush the role's cache.
     *
     * @return void
     */
    public function currentRoleFlushCache();
}
