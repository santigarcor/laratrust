<?php

namespace Laratrust\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface LaratrustHasPermissionsInterface
{

    /**
     * Many-to-Many relations with Permission.
     *
     * @return MorphToMany
     */
    public function permissions();



    /**
     * Check if Model has a permission by its name.
     *
     * @param  string|array  $permission Permission string or array of permissions.
     * @param  string|bool  $team      Team name or requiredAll roles.
     * @param  bool  $requireAll All roles in the array are required.
     * @return bool
     */
    public function hasPermission($permission, $team = null, $requireAll = false);

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param  mixed  $permission
     * @param  mixed  $team
     * @return static
     */
    public function attachPermission($permission, $team = null);

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param  mixed  $permission
     * @param  mixed  $team
     * @return static
     */
    public function detachPermission($permission, $team = null);

    /**
     * Attach multiple permissions to a user.
     *
     * @param  mixed  $permissions
     * @param  mixed  $team
     * @return static
     */
    public function attachPermissions($permissions = [], $team = null);

    /**
     * Detach multiple permissions from a user.
     *
     * @param  mixed  $permissions
     * @param  mixed  $team
     * @return static
     */
    public function detachPermissions($permissions = [], $team = null);

    /**
     * Sync roles to the user.
     *
     * @param  array  $permissions
     * @param  null  $team
     * @return static
     */
    public function syncPermissions($permissions = [], $team = null);


}
