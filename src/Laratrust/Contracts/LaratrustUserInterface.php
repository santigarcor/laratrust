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
     * @param string|bool  $group      Group name or requiredAll roles.
     * @param bool         $requireAll All roles in the array are required.
     *
     * @return bool
     */
    public function hasRole($name, $group = null, $requireAll = false);

    /**
     * Check if user has a permission by its name.
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param string|bool  $group      Group name or requiredAll roles.
     * @param bool         $requireAll All roles in the array are required.
     *
     * @return bool
     */
    public function hasPermission($permission, $group = null, $requireAll = false);

    /**
     * Check if user has a permission by its name. Calls hasPermission method
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param string|bool  $group      Group name or requiredAll roles.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function can($permission, $group = null, $requireAll = false);

    /**
     * Check if user has a permission by its name. Calls hasPermission method
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param string|bool  $group      Group name or requiredAll roles.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function isAbleTo($permission, $group = null, $requireAll = false);
    
    /**
     * Checks role(s) and permission(s).
     *
     * @param string|array $roles       Array of roles or comma separated string
     * @param string|array $permissions Array of permissions or comma separated string.
     * @param string|bool  $group      Group name or requiredAll roles.
     * @param array        $options     validate_all (true|false) or return_type (boolean|array|both)
     *
     * @throws \InvalidArgumentException
     *
     * @return array|bool
     */
    public function ability($roles, $permissions, $group = null, $options = []);
    
    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $role
     * @param mixed $group
     */
    public function attachRole($role, $group = null);
    
    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $role
     * @param mixed $group
     */
    public function detachRole($role, $group = null);
    
    /**
     * Attach multiple roles to a user
     *
     * @param mixed $roles
     * @param mixed $group
     */
    public function attachRoles($roles = [], $group = null);
    
    /**
     * Detach multiple roles from a user
     *
     * @param mixed $roles
     * @param mixed $group
     */
    public function detachRoles($roles = [], $group = null);

    /**
     * Sync multiple roles in a user
     * @param array $roles
     * @param mixed $group
     */
    public function syncRoles($roles = [], $group = null);

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed  $permission
     * @param mixed $group
     */
    public function attachPermission($permission, $group = null);
    
    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed  $permission
     * @param mixed $group
     */
    public function detachPermission($permission, $group = null);
    
    /**
     * Attach multiple permissions to a user
     *
     * @param mixed  $permissions
     * @param mixed $group
     */
    public function attachPermissions($permissions = [], $group = null);
    
    /**
     * Detach multiple permissions from a user
     *
     * @param mixed  $permissions
     * @param mixed $group
     */
    public function detachPermissions($permissions = [], $group = null);

    /**
     * Sync multiple roles in a user
     * @param  array $permissions
     * @param mixed $group
     */
    public function syncPermissions($permissions = [], $group = null);

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
