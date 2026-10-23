<?php

declare(strict_types=1);

namespace Laratrust\Checkers;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laratrust\Contracts\Role;
use Laratrust\Helper;
use Laratrust\Support\PermissionLookup;
use Laratrust\Support\PermissionRegistry;

/**
 * @internal
 */
final readonly class RoleChecker
{
    private PermissionRegistry $registry;

    public function __construct(private Model&Role $role)
    {
        $this->registry = app(PermissionRegistry::class);
    }

    /**
     * Check if the role has a permission by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function hasPermission(string|array|BackedEnum $permission, bool $requireAll = false): bool
    {
        $names = Collection::make(Helper::standardize($permission, true));

        if ($names->isEmpty()) {
            return true;
        }

        $granted = $this->registry->permissionsForRole($this->role);

        return $requireAll
            ? $names->every(fn (string $name): bool => PermissionLookup::grants($granted, $name))
            : $names->contains(fn (string $name): bool => PermissionLookup::grants($granted, $name));
    }

    /**
     * Forget the role's permissions from the cache and the current request.
     */
    public function flush(): void
    {
        $this->registry->forgetRole($this->role);
    }
}
