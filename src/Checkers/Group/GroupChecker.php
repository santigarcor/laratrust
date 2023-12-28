<?php

declare(strict_types=1);

namespace Laratrust\Checkers\Group;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Laratrust\Contracts\Group;

abstract class GroupChecker
{
  public function __construct(protected Group|Model $group)
  {
  }

  abstract public function currentGroupHasPermission(
    string|array|BackedEnum $permission,
    bool $requireAll = false
  ): bool;

  abstract public function currentGroupHasRole(
    string|array|BackedEnum $role,
    bool $requireAll = false
  ): bool;

  abstract public function currentGroupFlushCache(): void;
}
