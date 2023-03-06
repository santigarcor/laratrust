<?php

namespace Laratrust\Checkers\Role;

use Illuminate\Database\Eloquent\Model;

abstract class RoleChecker
{
    protected Model $role;

    public function __construct(Model $role)
    {
        $this->role = $role;
    }

    abstract public function currentRoleHasPermission(
        string|array $permission,
        bool $requireAll = false
    ): bool;

    abstract public function currentRoleFlushCache(): void;
}
