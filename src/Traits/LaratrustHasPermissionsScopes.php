<?php

namespace Laratrust\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait LaratrustHasPermissionsScopes
 *
 * @method static Builder wherePermissionIs($permission = '', $boolean = 'and')
 * @method static Builder orWherePermissionIs($permission = '')
 * @method static Builder whereDoesntHavePermission()
 */
trait LaratrustHasPermissionsScopes
{

    /**
     * This scope allows to retrieve the users with a specific permission.
     * @param  Builder  $query
     * @param  string  $permission
     * @return Builder
     */
    public function scopeWherePermissionIs($query, $permission = '', $boolean = 'and')
    {
        $method = $boolean == 'and' ? 'where' : 'orWhere';

        return $query->$method(function ($query) use ($permission) {
            $method = is_array($permission) ? 'whereIn' : 'where';

            $query->whereHas('roles.permissions', function ($permissionQuery) use ($method, $permission) {
                $permissionQuery->$method('name', $permission);
            })->orWhereHas('permissions', function ($permissionQuery) use ($method, $permission) {
                $permissionQuery->$method('name', $permission);
            });
        });
    }

    /**
     * This scope allows to retrive the users with a specific permission.
     *
     * @param  Builder  $query
     * @param  string  $permission
     * @return Builder
     */
    public function scopeOrWherePermissionIs($query, $permission = '')
    {
        return $this->scopeWherePermissionIs($query, $permission, 'or');
    }


    /**
     * Filter by the users that don't have permissions assigned.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeWhereDoesntHavePermission($query)
    {
        return $query->where(function ($query) {
            $query->doesntHave('permissions')
                ->orDoesntHave('roles.permissions');
        });
    }
}
