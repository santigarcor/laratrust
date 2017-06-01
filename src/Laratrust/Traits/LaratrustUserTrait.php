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
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function roles()
    {
        return $this->morphToMany(
            Config::get('laratrust.role'),
            'user',
            Config::get('laratrust.role_user_table'),
            Config::get('laratrust.user_foreign_key'),
            Config::get('laratrust.role_foreign_key')
        )->withPivot(Config::get('laratrust.group_foreign_key'));
    }

    /**
     * Many-to-Many relations with Permission.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function permissions()
    {
        return $this->morphToMany(
            Config::get('laratrust.permission'),
            'user',
            Config::get('laratrust.permission_user_table'),
            Config::get('laratrust.user_foreign_key'),
            Config::get('laratrust.permission_foreign_key')
        )->withPivot(Config::get('laratrust.group_foreign_key'));
    }

    /**
     * Many-to-Many relations with Group.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function groups()
    {
        return $this->morphToMany(
            Config::get('laratrust.group'),
            'user',
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
     * @param string|bool  $group      Group name or requiredAll roles.
     * @param bool         $requireAll All roles in the array are required.
     *
     * @return bool
     */
    public function hasRole($name, $group = null, $requireAll = false)
    {
        list($group, $requireAll) = $this->assignRealValuesTo($group, $requireAll, 'is_bool');
        $groupForeignKey = Config::get('laratrust.group_foreign_key');

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
            if ($role->name == $name && $role->pivot->$groupForeignKey == $group) {
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
     * @param bool         $requireAll All roles in the array are required.
     *
     * @return bool
     */
    public function hasPermission($permission, $group = null, $requireAll = false)
    {
        list($group, $requireAll) = $this->assignRealValuesTo($group, $requireAll, 'is_bool');
        $groupForeignKey = Config::get('laratrust.group_foreign_key');

        if (is_array($permission)) {
            if (empty($permission)) {
                return true;
            }

            foreach ($permission as $permissionName) {
                $hasPermission = $this->hasPermission($permissionName, $group);

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

        if (!is_null($group)) {
            $group = call_user_func_array(
                    [Config::get('laratrust.group'), 'where'],
                    ['name', $group]
                )->first();
            $group = is_null($group) ? $group : $group->getKey();
        }

        foreach ($this->cachedPermissions() as $perm) {
            if ($perm->pivot->$groupForeignKey != $group) {
                continue;
            }

            if (str_is($permission, $perm->name)) {
                return true;
            }
        }

        foreach ($this->cachedRoles() as $role) {
            if ($role->pivot->$groupForeignKey != $group) {
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
        return $this->hasPermission($permission, $group, $requireAll);
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
    public function isAbleTo($permission, $group = null, $requireAll = false)
    {
        return $this->hasPermission($permission, $group, $requireAll);
    }

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
    public function ability($roles, $permissions, $group = null, $options = [])
    {
        list($group, $options) = $this->assignRealValuesTo($group, $options, 'is_array');

        // Convert string to array if that's what is passed in.
        if (!is_array($roles)) {
            $roles = explode(',', $roles);
        }
        if (!is_array($permissions)) {
            $permissions = explode(',', $permissions);
        }

        // Set up default values and validate options.
        $options = $this->checkOrSet('validate_all', $options, [false, true]);
        $options = $this->checkOrSet('return_type', $options, ['boolean', 'array', 'both']);

        // Loop through roles and permissions and check each.
        $checkedRoles = [];
        $checkedPermissions = [];
        foreach ($roles as $role) {
            $checkedRoles[$role] = $this->hasRole($role, $group);
        }
        foreach ($permissions as $permission) {
            $checkedPermissions[$permission] = $this->hasPermission($permission, $group);
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
     * @param mixed $group
     * @return static
     */
    public function attachRole($role, $group = null)
    {
        $group = $this->getIdFor($group, 'group');
        $groupForeignKey = Config::get('laratrust.group_foreign_key');

        if ($this->roles()->wherePivot($groupForeignKey, $group)->count()) {
            return $this;
        }

        $this->roles()->attach(
            $this->getIdFor($role),
            [$groupForeignKey => $group]
        );
        $this->flushCache();

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $role
     * @param mixed $group
     * @return static
     */
    public function detachRole($role, $group = null)
    {
        $groupForeignKey = Config::get('laratrust.group_foreign_key');

        $this->roles()
            ->wherePivot($groupForeignKey, $this->getIdFor($group, 'group'))
            ->detach($this->getIdFor($role));
        $this->flushCache();

        return $this;
    }

    /**
     * Attach multiple roles to a user
     *
     * @param mixed $roles
     * @param mixed $group
     * @return static
     */
    public function attachRoles($roles = [], $group = null)
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
     * @param mixed $group
     * @return static
     */
    public function detachRoles($roles = [], $group = null)
    {
        if (empty($roles)) {
            $roles = $this->roles()->get();
        }

        foreach ($roles as $role) {
            $this->detachRole($role, $group);
        }

        return $this;
    }

    /**
     * Sync roles to the user
     * @param array $roles
     * @param mixed $group
     * @return static
     */
    public function syncRoles($roles = [], $group = null)
    {
        $groupForeignKey = Config::get('laratrust.group_foreign_key');
        $mappedRoles = [];

        foreach ($roles as $role) {
            $mappedRoles[$this->getIdFor($role)] = [$groupForeignKey => $group];
        }

        $this->roles()->sync($mappedRoles);
        $this->flushCache();

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param mixed $permission
     * @param mixed $group
     * @return static
     */
    public function attachPermission($permission, $group = null)
    {
        $group = $this->getIdFor($group, 'group');
        $groupForeignKey = Config::get('laratrust.group_foreign_key');

        if ($this->permissions()->wherePivot($groupForeignKey, $group)->count()) {
            return $this;
        }

        $this->permissions()->attach(
            $this->getIdFor($permission, 'permission'),
            [$groupForeignKey => $group]
        );
        $this->flushCache();

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param mixed $permission
     * @param mixed $group
     * @return static
     */
    public function detachPermission($permission, $group = null)
    {
        $groupForeignKey = Config::get('laratrust.group_foreign_key');

        $this->permissions()
            ->wherePivot($groupForeignKey, $this->getIdFor($group, 'group'))
            ->detach($this->getIdFor($permission, 'permission'));
        $this->flushCache();

        return $this;
    }

    /**
     * Attach multiple permissions to a user
     *
     * @param mixed $permissions
     * @param mixed $group
     * @return static
     */
    public function attachPermissions($permissions = [], $group = null)
    {
        foreach ($permissions as $permission) {
            $this->attachPermission($permission, $group);
        }

        return $this;
    }

    /**
     * Detach multiple permissions from a user
     *
     * @param mixed $permissions
     * @param mixed $group
     * @return static
     */
    public function detachPermissions($permissions = [], $group = null)
    {
        if (!$permissions) {
            $permissions = $this->permissions()->get();
        }

        foreach ($permissions as $permission) {
            $this->detachPermission($permission, $group);
        }

        return $this;
    }

    /**
     * Sync roles to the user
     * @param  array  $permissions
     * @return static
     */
    public function syncPermissions($permissions = [], $group = null)
    {
        $groupForeignKey = Config::get('laratrust.group_foreign_key');
        $mappedPerms = [];

        foreach ($permissions as $permission) {
            $mappedPerms[$this->getIdFor($permission, 'permission')] = [$groupForeignKey => $group];
        }

        $this->permissions()->sync($mappedPerms);
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
        $options = $this->checkOrSet('requireAll', $options, [false, true]);
        $options = $this->checkOrSet('group', $options, [null]);
        $options = $this->checkOrSet('foreignKeyName', $options, [null]);

        return $this->hasRole($role, $options['group'], $options['requireAll'])
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
        $options = $this->checkOrSet('requireAll', $options, [false, true]);
        $options = $this->checkOrSet('foreignKeyName', $options, [null]);
        $options = $this->checkOrSet('group', $options, [null]);

        return $this->hasPermission($permission, $options['group'], $options['requireAll'])
                && $this->owns($thing, $options['foreignKeyName']);
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
     * Checks if the option exists inside the array
     * if not sets a the first option inside the default
     * values array
     * @param  string $option
     * @param  array $array
     * @param  array $possibleValues
     * @param  int $defaultIndex
     * @return array
     */
    protected function checkOrSet($option, $array, $possibleValues)
    {
        if (!isset($array[$option])) {
            $array[$option] = $possibleValues[0];

            return $array;
        }

        $ignoredOptions = ['group', 'foreignKeyName'];

        if (!in_array($option, $ignoredOptions) && !in_array($array[$option], $possibleValues, true)) {
            throw new InvalidArgumentException();
        }

        return $array;
    }

    /**
     * Gets the it from an array or object
     * @param  mixed  $object
     * @param  string $type
     * @return int
     */
    private function getIdFor($object, $type = 'role')
    {
        if (is_null($object)) {
            return null;
        } elseif (is_object($object)) {
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

    
    /**
     * Assing the real values to the group and requireAllOrOptions parameters
     * @param  mixed $group
     * @param  mixed $requireAllOrOptions
     * @return array
     */
    private function assignRealValuesTo($group, $requireAllOrOptions, $method)
    {
        return [
            ($method($group) ? null : $group),
            ($method($group) ? $group : $requireAllOrOptions),
        ];
    }
}
