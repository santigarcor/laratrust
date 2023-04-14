<?php

declare(strict_types=1);

namespace Laratrust\Checkers\Role;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Laratrust\Contracts\Role;

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
