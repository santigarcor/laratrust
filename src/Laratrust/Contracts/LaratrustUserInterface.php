<?php

namespace Laratrust\Contracts;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */

interface LaratrustUserInterface
{
    /**
     * Many-to-Many relations with Role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles();

    /**
     * Many-to-Many relations with Role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function permissions();

    /**
     * Checks if the user has a role by its name.
     *
     * @param string|array $name       Role name or array of role names.
     * @param bool         $requireAll All roles in the array are required.
     *
     * @return bool
     */
    public function hasRole($name, $requireAll = false);

    /**
     * Check if user has a permission by its name.
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function hasPermission($permission, $requireAll = false);

    /**
     * Check if user has a permission by its name. Calls hasPermission method
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function isAbleTo($permission, $requireAll = false);

    /**
     * Check if user has a permission by its name. Calls hasPermission method
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function can($permission, $requireAll = false);
    
    /**
     * Checks role(s) and permission(s).
     *
     * @param string|array $roles       Array of roles or comma separated string
     * @param string|array $permissions Array of permissions or comma separated string.
     * @param array        $options     validate_all (true|false) or return_type (boolean|array|both)
     *
     * @throws \InvalidArgumentException
     *
     * @return array|bool
     */
    public function ability($roles, $permissions, $options = []);
    
    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $role
     */
    public function attachRole($role);
    
    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $role
     */
    public function detachRole($role);
    
    /**
     * Attach multiple roles to a user
     *
     * @param mixed $roles
     */
    public function attachRoles($roles);
    
    /**
     * Detach multiple roles from a user
     *
     * @param mixed $roles
     */
    public function detachRoles($roles);

    /**
     * Sync multiple roles in a user
     * @param  array $roles
     */
    public function syncRoles($roles);

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed  $permission
     */
    public function attachPermission($permission);
    
    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed  $permission
     */
    public function detachPermission($permission);
    
    /**
     * Attach multiple permissions to a user
     *
     * @param mixed  $permissions
     */
    public function attachPermissions($permissions);
    
    /**
     * Detach multiple permissions from a user
     *
     * @param mixed  $permissions
     */
    public function detachPermissions($permissions);

    /**
     * Sync multiple roles in a user
     * @param  array $permissions
     */
    public function syncPermissions($permissions);

    /**
     * Checks if the user owns the thing
     * @param  mixed $thing
     */
    public function owns($thing);
    
    /**
     * Checks if the user can do something and if he owns the thing
     * @param  string|array $permission
     * @param  Object $thing
     * @param  array  $options
     * @return boolean
     */
    public function hasRoleAndOwns($role, $thing, $options = []);

    /**
     * Checks if the user can do something and if he owns the thing
     * @param  string|array $permission
     * @param  Object $thing
     * @param  array  $options
     * @return boolean
     */
    public function canAndOwns($permission, $thing, $options = []);
}
