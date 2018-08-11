<?php

namespace Laratrust\Traits;

trait LaratrustHasScopes
{
    /**
     * This scope allows to retrive the users with a specific role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @param  string  $boolean
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereRoleIs($query, $role = '', $boolean = 'and')
    {
        $method = $boolean == 'and' ? 'whereHas' : 'orWhereHas';

        return $query->$method('roles', function ($roleQuery) use ($role) {
            $roleQuery->where('name', $role);
        });
    }

    /**
     * This scope allows to retrive the users with a specific role.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $role
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrWhereRoleIs($query, $role = '')
    {
        return $this->scopeWhereRoleIs($query, $role, 'or');
    }

    /**
     * This scope allows to retrieve the users with a specific permission.
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $permission
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePermissionIs($query, $permission = '', $boolean = 'and')
    {
        $method = $boolean == 'and' ? 'where' : 'orWhere';

        return $query->$method(function ($query) use ($permission) {
            $query->whereHas('roles.permissions', function ($permissionQuery) use ($permission) {
                $permissionQuery->where('name', $permission);
            })->orWhereHas('permissions', function ($permissionQuery) use ($permission) {
                $permissionQuery->where('name', $permission);
            });
        });
    }

    /**
     * This scope allows to retrive the users with a specific permission.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $permission
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrWherePermissionIs($query, $permission = '')
    {
        return $this->scopeWherePermissionIs($query, $permission, 'or');
    }
}
