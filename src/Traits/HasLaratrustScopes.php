<?php

declare(strict_types=1);

namespace Laratrust\Traits;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * @method Builder whereHasRole(string|array|BackedEnum $role = '', string $boolean = 'and')
 * @method Builder orWhereHasRole(string|array|BackedEnum $role = '')
 * @method Builder whereHasPermission(string|array|BackedEnum $permission = '', string $boolean = 'and')
 * @method Builder orWhereHasPermission(string|array|BackedEnum $permission = '')
 * @method Builder whereDoesntHaveRoles()
 * @method Builder whereDoesntHavePermissions()
 * @method static Builder whereHasRole(string|array|BackedEnum $role = '', string $boolean = 'and')
 * @method static Builder orWhereHasRole(string|array|BackedEnum $role = '')
 * @method static Builder whereHasPermission(string|array|BackedEnum $permission = '', string $boolean = 'and')
 * @method static Builder orWhereHasPermission(string|array|BackedEnum $permission = '')
 * @method static Builder whereDoesntHaveRoles()
 * @method static Builder whereDoesntHavePermissions()
 */
trait HasLaratrustScopes
{
    /**
     * This scope allows to retrive the users with a specific role.
     */
    public function scopeWhereHasRole(
        Builder $query,
        string|array|BackedEnum $role = '',
        string $boolean = 'and'
    ): Builder {
        $method = $boolean == 'and' ? 'whereHas' : 'orWhereHas';

        return $query->$method('roles', function ($roleQuery) use ($role) {
            $method = is_array($role) ? 'whereIn' : 'where';

            $roleQuery
                ->$method('name', $role)
                ->orWhereHas('groups', function ($groupQuery) use ($role, $method) {
                    $groupQuery->orWhereHas('roles', function ($rolePermissionQuery) use ($role, $method) {
                        $rolePermissionQuery->$method('name', $role);
                    });
                });
        });
    }

    /**
     * This scope allows to retrive the users with a specific role.
     */
    public function scopeOrWhereHasRole(
        Builder $query,
        string|array|BackedEnum $role = '',
    ): Builder {
        return $this->scopeWhereHasRole($query, $role, 'or');
    }

    /**
     * This scope allows to retrieve the users with a specific permission.
     */
    public function scopeWhereHasPermission(
        Builder $query,
        string|array|BackedEnum $permission = '',
        string $boolean = 'and'
    ): Builder {
        $method = $boolean == 'and' ? 'where' : 'orWhere';

        return $query->$method(function ($query) use ($permission) {
            $method = is_array($permission) ? 'whereIn' : 'where';

            $query
                ->whereHas(
                    'groups.permissions',
                    fn ($permissionQuery) => $permissionQuery->$method('name', $permission)
                )
                ->orWhereHas(
                    'roles.permissions',
                    fn ($permissionQuery) => $permissionQuery->$method('name', $permission)
                )
                ->orWhereHas(
                    'permissions',
                    fn ($permissionQuery) => $permissionQuery->$method('name', $permission)
                )
                ->orWhereHas('groups', function ($groupQuery) use ($permission, $method) {
                    $groupQuery->orWhereHas('roles.permissions', function ($rolePermissionQuery) use ($permission, $method) {
                        $rolePermissionQuery->$method('name', $permission);
                    });

                    $groupQuery->orWhereHas('permissions', function ($permissionQuery) use ($permission, $method) {
                        $permissionQuery->$method('name', $permission);
                    });
                });
        });
    }

    /**
     * This scope allows to retrive the users with a specific permission.
     */
    public function scopeOrWhereHasPermission(
        Builder $query,
        string|array|BackedEnum $permission = ''
    ): Builder {
        return $this->scopeWhereHasPermission($query, $permission, 'or');
    }

    /**
     * Filter by the users that don't have roles assigned.
     */
    public function scopeWhereDoesntHaveRoles(Builder $query): Builder
    {
        return $query->doesntHave('roles')
            ->doesntHave('groups.roles');
    }

    /**
     * Filter by the users that don't have permissions assigned.
     */
    public function scopeWhereDoesntHavePermissions(Builder $query): Builder
    {
        return $query->where(function ($query) {
            // TODO: Might need to dig deeper here, check for permissions associated to roles that exist on groups
            $query->doesntHave('permissions')
                ->DoesntHave('groups.permissions')
                ->DoesntHave('roles.permissions');
        });
    }
}
