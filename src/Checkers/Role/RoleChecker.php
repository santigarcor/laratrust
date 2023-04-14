<?php

declare(strict_types=1);

namespace Laratrust\Checkers\Role;

use BackedEnum;
use Laratrust\Contracts\Role;
use Illuminate\Database\Eloquent\Model;

abstract class RoleChecker
{
    public function __construct(protected Role|Model $role)
    {
    }

    abstract public function currentRoleHasPermission(
        string|array|BackedEnum $permission,
        bool $requireAll = false
    ): bool;

    abstract public function currentRoleFlushCache(): void;
}
