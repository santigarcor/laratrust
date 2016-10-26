<?php

namespace Laratrust\Traits;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

trait LaratrustUserTrait
{
    /**
     * Tries to return all the cached roles of the user
     * and if it can't bring the roles from the cache,
     * it would bring them back from the DB
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function cachedRoles()
    {
        $cacheKey = 'laratrust_roles_for_user_' . $this->getKey();

        return Cache::remember($cacheKey, Config::get('cache.ttl', 60), function () {
            return $this->roles()->get();
        });
    }
    /**
     * Tries to return all the cached permissions of the user
     * and if it can't bring the permissions from the cache,
     * it would bring them back from the DB
     * @return Illuminate\Database\Eloquent\Collection
     */
    public function cachedPermissions()
    {
        $cacheKey = 'laratrust_permissions_for_user_' . $this->getKey();

        return Cache::remember($cacheKey, Config::get('cache.ttl', 60), function () {
            return $this->permissions()->get();
        });
    }

    /**
     * Many-to-Many relations with Role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(
            Config::get('laratrust.role'),
            Config::get('laratrust.role_user_table'),
            Config::get('laratrust.user_foreign_key'),
            Config::get('laratrust.role_foreign_key')
        )->withPivot(Config::get('laratrust.group_foreign_key'));
    }

    /**
     * Many-to-Many relations with Permission.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(
            Config::get('laratrust.permission'),
            Config::get('laratrust.permission_user_table'),
            Config::get('laratrust.user_foreign_key'),
            Config::get('laratrust.permission_foreign_key')
        );
    }

    /**
     * Many-to-Many relations with Group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(
            Config::get('laratrust.group'),
            Config::get('laratrust.role_user_table'),
            Config::get('laratrust.user_foreign_key'),
            Config::get('laratrust.group_foreign_key')
        )->withPivot(Config::get('laratrust.role_foreign_key'));
    }

    /**
     * Boot the user model
     * Attach event listener to remove the many-to-many records when trying to delete
     * Will NOT delete any records if the user model uses soft deletes.
     *
     * @return void|bool
     */
    public static function bootLaratrustUserTrait()
    {
        $flushCache = function ($user) {
            $user->flushCache();
            return true;
        };

        // If the user doesn't use SoftDeletes
        if (method_exists(Config::get('auth.providers.users.model'), 'restored')) {
            static::restored($flushCache);
        }

        static::deleted($flushCache);
        static::saved($flushCache);

        static::deleting(function ($user) {
            if (!method_exists(Config::get('auth.providers.users.model'), 'bootSoftDeletes')) {
                $user->roles()->sync([]);
            }
        });
    }

    /**
     * Checks if the user has a role by its name.
     *
     * @param string|array $name       Role name or array of role names.
     * @param string|bool  $group      Group name or requiredAll roles.
     * @param bool         $requireAll All roles in the array are required.
     *
     * @return bool
     */
    public function hasRole($name, $group = null, $requireAll = false)
    {
        $requireAll = is_bool($group) ? $group : $requireAll;
        $group = is_bool($group) ? null : $group;

        if (is_array($name)) {
            if (empty($name)) {
                return true;
            }

            foreach ($name as $roleName) {
                $hasRole = $this->hasRole($roleName, $group);

                if ($hasRole && !$requireAll) {
                    return true;
                } elseif (!$hasRole && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the roles were found
            // If we've made it this far and $requireAll is TRUE, then ALL of the roles were found.
            // Return the value of $requireAll;
            return $requireAll;
        }

        if (!is_null($group)) {
            $group = call_user_func_array(
                        [Config::get('laratrust.group'), 'where'],
                        ['name', $group]
                    )->first();
            $group = is_null($group) ? $group : $group->getKey();
        }

        foreach ($this->cachedRoles() as $role) {
            if ($role->name == $name && $role->pivot->group_id == $group) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param string|bool  $group      Group name or requiredAll roles.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function can($permission, $group = null, $requireAll = false)
    {
        $requireAll = is_bool($group) ? $group : $requireAll;
        $group = is_bool($group) ? null : $group;

        if (is_array($permission)) {
            if (empty($permission)) {
                return true;
            }

            foreach ($permission as $permName) {
                $hasPerm = $this->can($permName, $group);

                if ($hasPerm && !$requireAll) {
                    return true;
                } elseif (!$hasPerm && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the perms were found
            // If we've made it this far and $requireAll is TRUE, then ALL of the perms were found.
            // Return the value of $requireAll;
            return $requireAll;
        }

        if (!is_null($group)) {
            $group = call_user_func_array(
                    [Config::get('laratrust.group'), 'where'],
                    ['name', $group]
                )->first();
            $group = is_null($group) ? $group : $group->getKey();
        }

        foreach ($this->cachedPermissions() as $perm) {
            if (str_is($permission, $perm->name)) {
                return true;
            }
        }

        foreach ($this->cachedRoles() as $role) {
            // Validate against the Permission table
            if ($role->pivot->group_id != $group) {
                continue;
            }

            foreach ($role->cachedPermissions() as $perm) {
                if (str_is($permission, $perm->name)) {
                    return true;
                }
            }
        }

        return false;
    }

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
    public function ability($roles, $permissions, $group = null, $options = [])
    {
        $options = is_array($group) ? $group : $options;
        $group = is_array($group) ? null : $group;

        // Convert string to array if that's what is passed in.
        if (!is_array($roles)) {
            $roles = explode(',', $roles);
        }
        if (!is_array($permissions)) {
            $permissions = explode(',', $permissions);
        }

        // Set up default values and validate options.
        if (!isset($options['validate_all'])) {
            $options['validate_all'] = false;
        } else {
            if ($options['validate_all'] !== true && $options['validate_all'] !== false) {
                throw new InvalidArgumentException();
            }
        }
        if (!isset($options['return_type'])) {
            $options['return_type'] = 'boolean';
        } else {
            if ($options['return_type'] != 'boolean' &&
                $options['return_type'] != 'array' &&
                $options['return_type'] != 'both') {
                throw new InvalidArgumentException();
            }
        }

        // Loop through roles and permissions and check each.
        $checkedRoles = [];
        $checkedPermissions = [];
        foreach ($roles as $role) {
            $checkedRoles[$role] = $this->hasRole($role, $group);
        }
        foreach ($permissions as $permission) {
            $checkedPermissions[$permission] = $this->can($permission, $group);
        }

        // If validate all and there is a false in either
        // Check that if validate all, then there should not be any false.
        // Check that if not validate all, there must be at least one true.
        if (($options['validate_all'] && !(in_array(false, $checkedRoles) || in_array(false, $checkedPermissions))) ||
            (!$options['validate_all'] && (in_array(true, $checkedRoles) || in_array(true, $checkedPermissions)))) {
            $validateAll = true;
        } else {
            $validateAll = false;
        }

        // Return based on option
        if ($options['return_type'] == 'boolean') {
            return $validateAll;
        } elseif ($options['return_type'] == 'array') {
            return ['roles' => $checkedRoles, 'permissions' => $checkedPermissions];
        } else {
            return [$validateAll, ['roles' => $checkedRoles, 'permissions' => $checkedPermissions]];
        }
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $role
     * @return Illuminate\Database\Eloquent\Model
     */
    public function attachRole($role, $group = null)
    {
        if (is_object($role)) {
            $role = $role->getKey();
        }

        if (is_array($role)) {
            $role = $role['id'];
        }

        if (!is_object($group) && $group != null) {
            throw new InvalidArgumentException;
        }
        
        if (!is_null($group)) {
            $group = $group->getKey();
        }

        $this->roles()->wherePivot(Config::get('laratrust.group_foreign_key'), $group)->detach($role);
        $this->roles()->attach($role, [Config::get('laratrust.group_foreign_key') => $group]);
        $this->flushCache();

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $role
     * @return Illuminate\Database\Eloquent\Model
     */
    public function detachRole($role, $group = null)
    {
        if (is_object($role)) {
            $role = $role->getKey();
        }

        if (is_array($role)) {
            $role = $role['id'];
        }

        if (!is_object($group) && $group != null) {
            throw new InvalidArgumentException;
        }

        if (!is_null($group)) {
            $group = $group->getKey();
        }

        $this->roles()->wherePivot(Config::get('laratrust.group_foreign_key'), $group)
            ->detach($role);
        $this->flushCache();

        return $this;
    }

    /**
     * Attach multiple roles to a user
     *
     * @param mixed $roles
     * @return Illuminate\Database\Eloquent\Model
     */
    public function attachRoles($roles, $group = null)
    {
        foreach ($roles as $role) {
            $this->attachRole($role, $group);
        }

        return $this;
    }

    /**
     * Detach multiple roles from a user
     *
     * @param mixed $roles
     * @return Illuminate\Database\Eloquent\Model
     */
    public function detachRoles($roles = null, $group = null)
    {
        if (!$roles) {
            $roles = $this->roles()->get();
        }
        
        foreach ($roles as $role) {
            $this->detachRole($role, $group);
        }

        return $this;
    }/**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $permission
     * @return Illuminate\Database\Eloquent\Model
     */
    public function attachPermission($permission)
    {
        if (is_object($permission)) {
            $permission = $permission->getKey();
        }

        if (is_array($permission)) {
            $permission = $permission['id'];
        }

        $this->permissions()->detach($permission);
        $this->permissions()->attach($permission);
        $this->flushCache();

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $permission
     * @return Illuminate\Database\Eloquent\Model
     */
    public function detachPermission($permission)
    {
        if (is_object($permission)) {
            $permission = $permission->getKey();
        }

        if (is_array($permission)) {
            $permission = $permission['id'];
        }

        $this->permissions()->detach($permission);
        $this->flushCache();

        return $this;
    }

    /**
     * Attach multiple permissions to a user
     *
     * @param mixed $permissions
     * @return Illuminate\Database\Eloquent\Model
     */
    public function attachPermissions($permissions)
    {
        foreach ($permissions as $permission) {
            $this->attachPermission($permission);
        }

        return $this;
    }

    /**
     * Detach multiple permissions from a user
     *
     * @param mixed $permissions
     * @return Illuminate\Database\Eloquent\Model
     */
    public function detachPermissions($permissions = null)
    {
        if (!$permissions) {
            $permissions = $this->permissions()->get();
        }
        
        foreach ($permissions as $permission) {
            $this->detachPermission($permission);
        }

        return $this;
    }

    /**
     * Checks if the user owns the thing
     * @param  Model $thing
     * @return boolean
     */
    public function owns($thing)
    {
        $foreignKeyName = snake_case(get_class($this). 'Id');

        return $thing->$foreignKeyName == $this->getKey();
    }

    /**
     * This scope allows to retrive users with an specific role
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @param  string $role
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereRoleIs($query, $role = '')
    {
        return $query->whereHas('roles', function ($roleQuery) use ($role) {
            $roleQuery->where('name', $role);
        });
    }

    /**
     * This scope allows to retrive users with an specific permission
     * @param  Illuminate\Database\Eloquent\Builder $query
     * @param  string $permission
     * @return Illuminate\Database\Eloquent\Builder
     */
    public function scopeWherePermissionIs($query, $permission = '')
    {
        return $query->whereHas('permissions', function ($permissionQuery) use ($permission) {
            $permissionQuery->where('name', $permission);
        });
    }

    /**
     * Flush the user's cache
     * @return void
     */
    public function flushCache()
    {
        Cache::forget('laratrust_roles_for_user_' . $this->getKey());
        Cache::forget('laratrust_permissions_for_user_' . $this->getKey());
    }
}
