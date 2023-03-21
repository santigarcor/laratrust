<?php

namespace Laratrust\Checkers\Role;

use Laratrust\Contracts\Role;
use Illuminate\Database\Eloquent\Model;

abstract class RoleChecker
{
    public function __construct(protected Role|Model $role)
    {
    }

    abstract public function currentRoleHasPermission(
        string|array $permission,
        bool $requireAll = false
    ): bool;

    abstract public function currentRoleFlushCache(): void;
}
