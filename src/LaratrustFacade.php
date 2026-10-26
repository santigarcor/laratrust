<?php

declare(strict_types=1);

namespace Laratrust;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool hasRole(string|array<string|\BackedEnum>|\BackedEnum $role, mixed $team = null, bool $requireAll = false)
 * @method static bool hasPermission(string|array<string|\BackedEnum>|\BackedEnum $permission, mixed $team = null, bool $requireAll = false)
 * @method static bool doesntHavePermission(string|array<string|\BackedEnum>|\BackedEnum $permission, mixed $team = null, bool $requireAll = false)
 * @method static bool isAbleTo(string|array<string|\BackedEnum>|\BackedEnum $permission, mixed $team = null, bool $requireAll = false)
 * @method static bool isNotAbleTo(string|array<string|\BackedEnum>|\BackedEnum $permission, mixed $team = null, bool $requireAll = false)
 * @method static void reset()
 * @method static bool|array<mixed> ability(string|array<string|\BackedEnum>|\BackedEnum $roles, string|array<string|\BackedEnum>|\BackedEnum $permissions, mixed $team = null, array<string, mixed> $options = [])
 *
 * @see Laratrust
 */
class LaratrustFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laratrust';
    }
}
