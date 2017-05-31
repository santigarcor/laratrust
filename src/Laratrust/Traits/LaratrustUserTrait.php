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
     * @return \Illuminate\Database\Eloquent\Collection
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
     * @return \Illuminate\Database\Eloquent\Collection
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
        return $this->morphToMany(
            Config::get('laratrust.role'),
            'user',
            Config::get('laratrust.role_user_table'),
            Config::get('laratrust.user_foreign_key'),
            Config::get('laratrust.role_foreign_key')
        );
    }

    /**
     * Many-to-Many relations with Permission.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function permissions()
    {
        return $this->morphToMany(
            Config::get('laratrust.permission'),
            'user',
            Config::get('laratrust.permission_user_table'),
            Config::get('laratrust.user_foreign_key'),
            Config::get('laratrust.permission_foreign_key')
        );
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
        if (method_exists(static::class, 'restored')) {
            static::restored($flushCache);
        }

        static::deleted($flushCache);
        static::saved($flushCache);

        static::deleting(function ($user) {
            if (method_exists($user, 'bootSoftDeletes') && !$user->forceDeleting) {
                return true;
            }

            $user->roles()->sync([]);
            $user->permissions()->sync([]);

            return true;
        });
    }

    /**
     * Checks if the user has a role by its name.
     *
     * @param string|array $name       Role name or array of role names.
     * @param bool         $requireAll All roles in the array are required.
     *
     * @return bool
     */
    public function hasRole($name, $requireAll = false)
    {
        if (is_array($name)) {
            if (empty($name)) {
                return true;
            }

            foreach ($name as $roleName) {
                $hasRole = $this->hasRole($roleName);

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

        foreach ($this->cachedRoles() as $role) {
            if ($role->name == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function hasPermission($permission, $requireAll = false)
    {
        if (is_array($permission)) {
            if (empty($permission)) {
                return true;
            }

            foreach ($permission as $permissionName) {
                $hasPermission = $this->hasPermission($permissionName);

                if ($hasPermission && !$requireAll) {
                    return true;
                } elseif (!$hasPermission && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the perms were found
            // If we've made it this far and $requireAll is TRUE, then ALL of the perms were found.
            // Return the value of $requireAll;
            return $requireAll;
        }

        foreach ($this->cachedPermissions() as $perm) {
            if (str_is($permission, $perm->name)) {
                return true;
            }
        }

        foreach ($this->cachedRoles() as $role) {
            foreach ($role->cachedPermissions() as $perm) {
                if (str_is($permission, $perm->name)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function can($permission, $requireAll = false)
    {
        return $this->hasPermission($permission, $requireAll);
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param string|array $permission Permission string or array of permissions.
     * @param bool         $requireAll All permissions in the array are required.
     *
     * @return bool
     */
    public function isAbleTo($permission, $requireAll = false)
    {
        return $this->hasPermission($permission, $requireAll);
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
    public function ability($roles, $permissions, $options = [])
    {
        // Convert string to array if that's what is passed in.
        if (!is_array($roles)) {
            $roles = explode(',', $roles);
        }
        if (!is_array($permissions)) {
            $permissions = explode(',', $permissions);
        }

        // Set up default values and validate options.
        $options = $this->checkOrSetDefaultOption('validate_all', $options, [false, true]);
        $options = $this->checkOrSetDefaultOption('return_type', $options, ['boolean', 'array', 'both']);

        // Loop through roles and permissions and check each.
        $checkedRoles = [];
        $checkedPermissions = [];
        foreach ($roles as $role) {
            $checkedRoles[$role] = $this->hasRole($role);
        }
        foreach ($permissions as $permission) {
            $checkedPermissions[$permission] = $this->hasPermission($permission);
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
     * @return static
     */
    public function attachRole($role)
    {
        $this->roles()->attach($this->getIdFor($role));
        $this->flushCache();

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $role
     * @return static
     */
    public function detachRole($role)
    {
        $this->roles()->detach($this->getIdFor($role));
        $this->flushCache();

        return $this;
    }

    /**
     * Attach multiple roles to a user
     *
     * @param mixed $roles
     * @return static
     */
    public function attachRoles($roles)
    {
        foreach ($roles as $role) {
            $this->attachRole($role);
        }

        return $this;
    }

    /**
     * Detach multiple roles from a user
     *
     * @param mixed $roles
     * @return static
     */
    public function detachRoles($roles = null)
    {
        if (!$roles) {
            $roles = $this->roles()->get();
        }
        
        foreach ($roles as $role) {
            $this->detachRole($role);
        }

        return $this;
    }

    /**
     * Sync roles to the user
     * @param  array  $roles
     * @return static
     */
    public function syncRoles($roles = [])
    {
        $this->roles()->sync($roles);
        $this->flushCache();

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $permission
     * @return static
     */
    public function attachPermission($permission)
    {
        $this->permissions()->attach($this->getIdFor($permission, 'permission'));
        $this->flushCache();

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $permission
     * @return static
     */
    public function detachPermission($permission)
    {
        $this->permissions()->detach($this->getIdFor($permission, 'permission'));
        $this->flushCache();

        return $this;
    }

    /**
     * Attach multiple permissions to a user
     *
     * @param mixed $permissions
     * @return static
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
     * @return static
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
     * Sync roles to the user
     * @param  array  $permissions
     * @return static
     */
    public function syncPermissions($permissions = [])
    {
        $this->permissions()->sync($permissions);
        $this->flushCache();

        return $this;
    }

    /**
     * Checks if the user owns the thing
     * @param  Object $thing
     * @param  string $foreignKeyName
     * @return boolean
     */
    public function owns($thing, $foreignKeyName = null)
    {
        if ($thing instanceof \Laratrust\Contracts\Ownable) {
            $ownerKey = $thing->ownerKey();
        } else {
            $className = (new \ReflectionClass($this))->getShortName();
            $foreignKeyName = $foreignKeyName ?: snake_case($className . 'Id');
            $ownerKey = $thing->$foreignKeyName;
        }

        return $ownerKey == $this->getKey();
    }

    /**
     * Checks if the user has some role and if he owns the thing
     * @param  string|array $role
     * @param  Object $thing
     * @param  array  $options
     * @return boolean
     */
    public function hasRoleAndOwns($role, $thing, $options = [])
    {
        $options = $this->checkOrSetDefaultOption('requireAll', $options, [false, true]);
        $options['foreignKeyName'] = isset($options['foreignKeyName']) ? $options['foreignKeyName'] : null;

        return $this->hasRole($role, $options['requireAll'])
                && $this->owns($thing, $options['foreignKeyName']);
    }

    /**
     * Checks if the user can do something and if he owns the thing
     * @param  string|array $permission
     * @param  Object $thing
     * @param  array  $options
     * @return boolean
     */
    public function canAndOwns($permission, $thing, $options = [])
    {
        $options = $this->checkOrSetDefaultOption('requireAll', $options, [false, true]);
        $options['foreignKeyName'] = isset($options['foreignKeyName']) ? $options['foreignKeyName'] : null;

        return $this->hasPermission($permission, $options['requireAll'])
                && $this->owns($thing, $options['foreignKeyName']);
    }

    /**
     * Checks if the option exists inside the arrayToCheck
     * if not sets a the first option inside the default
     * values array
     * @param  string $option
     * @param  array $arrayToCheck
     * @param  array $defaultValues
     * @return array
     */
    protected function checkOrSetDefaultOption($option, $arrayToCheck, $defaultValues)
    {
        if (!isset($arrayToCheck[$option])) {
            $arrayToCheck[$option] = $defaultValues[0];

            return $arrayToCheck;
        }

        if (!in_array($arrayToCheck[$option], $defaultValues, true)) {
            throw new InvalidArgumentException();
        }

        return $arrayToCheck;
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
     * Flush the user's cache
     * @return void
     */
    public function flushCache()
    {
        Cache::forget('laratrust_roles_for_user_' . $this->getKey());
        Cache::forget('laratrust_permissions_for_user_' . $this->getKey());
    }

    /**
     * Gets the it from an array or object
     * @param  mixed  $object
     * @param  string $type
     * @return int
     */
    private function getIdFor($object, $type = 'role')
    {
        if (is_object($object)) {
            return $object->getKey();
        } elseif (is_array($object)) {
            return $object['id'];
        } elseif (is_numeric($object)) {
            return $object;
        } elseif (is_string($object)) {
            return call_user_func_array([
                Config::get("laratrust.{$type}"), 'where'
            ], ['name', $object])->firstOrFail()->getKey();
        }

        throw new InvalidArgumentException(
            'getIdFor function only accepts an integer, a Model object or an array with an "id" key'
        );
    }
}
