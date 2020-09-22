<?php

namespace Laratrust\Checkers\Role;

use Illuminate\Database\Eloquent\Model;
use Laratrust\Traits\LaratrustRoleTrait;

abstract class LaratrustRoleChecker
{
    /**
     * @var \Illuminate\Database\Eloquent\Model|LaratrustRoleTrait
     */
    protected $role;

    public function __construct(Model $role)
    {
        $this->role = $role;
    }

    /**
     * Checks if the role has a permission by its name.
     *
     * @param  string|array  $permission       Permission name or array of permission names.
     * @param  bool  $requireAll       All permissions in the array are required.
     * @return bool
     */
    abstract public function currentRoleHasPermission($permission, $requireAll = false);

    /**
     * Flush the role's cache.
     *
     * @return void
     */
    abstract public function currentRoleFlushCache();

    /**
     * @return string
     */
    protected function getPermissionsCacheKey(): string
    {
        return 'laratrust_permissions_for_role_'.$this->role->getKey();
    }
}
