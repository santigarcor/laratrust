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

    abstract public function currentRoleHasPermission($permission, $requireAll = false);

    abstract public function currentRoleFlushCache();

    /**
     * @return string
     */
    protected function getPermissionsCacheKey(): string
    {
        return 'laratrust_permissions_for_role_'.$this->role->getKey();
    }
}
