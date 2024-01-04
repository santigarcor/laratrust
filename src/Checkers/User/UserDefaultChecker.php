<?php

declare(strict_types=1);

namespace Laratrust\Checkers\User;

use BackedEnum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laratrust\Helper;
use UnexpectedValueException;

class UserDefaultChecker extends UserChecker
{
    public function getCurrentUserRoles(): array
    {
        $roles = Collection::make($this->userCachedRoles());
        return $roles->pluck('name')->toArray();
    }

    public function getCurrentUserGroups(): array
    {
        $groups = Collection::make($this->userCachedGroups());
        return $groups->pluck('name')->toArray();
    }

    public function getCurrentUserPermissions(): array
    {
        $permissions = Collection::make($this->userCachedPermissions());
        return $permissions->pluck('name')->toArray();
    }

    public function currentUserHasRole(
        string|array|BackedEnum $name,
        bool $requireAll = false
    ): bool {
        $name = Helper::standardize($name);

        if (is_array($name)) {
            if (empty($name)) {
                return true;
            }

            foreach ($name as $roleName) {
                $hasRole = $this->currentUserHasRole($roleName);

                if ($hasRole && !$requireAll) {
                    return true;
                } elseif (!$hasRole && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the roles were found.
            // If we've made it this far and $requireAll is TRUE, then ALL of the roles were found.
            // Return the value of $requireAll.
            return $requireAll;
        }

        foreach ($this->userCachedRoles() as $role) {
            if ($role['name'] == $name) {
                return true;
            }
        }

        return false;
    }

    public function currentUserHasGroup(
        string|array|BackedEnum $name,
        bool $requireAll = false
    ): bool {
        $name = Helper::standardize($name);

        if (is_array($name)) {
            if (empty($name)) {
                return true;
            }

            foreach ($name as $grouName) {
                $hasGroup = $this->currentUserHasGroup($grouName);

                if ($hasGroup && !$requireAll) {
                    return true;
                } elseif (!$hasGroup && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the groups were found.
            // If we've made it this far and $requireAll is TRUE, then ALL of the groups were found.
            // Return the value of $requireAll.
            return $requireAll;
        }

        foreach ($this->userCachedGroups() as $group) {
            if ($group['name'] == $name) {
                return true;
            }
        }

        return false;
    }

    public function currentUserHasPermission(
        string|array|BackedEnum $permission,
        bool $requireAll = false
    ): bool {
        $permission = Helper::standardize($permission);

        if (is_array($permission)) {
            if (empty($permission)) {
                return true;
            }

            foreach ($permission as $permissionName) {
                $hasPermission = $this->currentUserHasPermission($permissionName);

                if ($hasPermission && !$requireAll) {
                    return true;
                } elseif (!$hasPermission && $requireAll) {
                    return false;
                }
            }

            // If we've made it this far and $requireAll is FALSE, then NONE of the perms were found.
            // If we've made it this far and $requireAll is TRUE, then ALL of the perms were found.
            // Return the value of $requireAll.
            return $requireAll;
        }

        foreach ($this->userCachedPermissions() as $perm) {
            if (Str::is($permission, $perm['name'])) {
                return true;
            }
        }

        // THERE IS NO NEED TO HIDRATE ROLE PERMISSIONS ON CACHE ARE RESOLVED.

        // foreach ($this->userCachedRoles() as $role) {
        //     $role = $this->hidrateRole(Config::get('laratrust.models.role'), $role);

        //     if ($role->hasPermission($permission)) {
        //         return true;
        //     }
        // }

        // foreach ($this->userCachedGroups() as $group) {
        //     $group = $this->hidrateGroup(Config::get('laratrust.models.group'), $group);

        //     if ($group->hasPermission($permission)) {
        //         return true;
        //     }
        // }

        return false;
    }

    public function currentUserFlushCache(bool $recreate = true)
    {
        Cache::forget('laratrust_cache_for_' . $this->user->getKey());
        if ($recreate) {
            $this->resolvePermissions();
        }
    }

    /**
     * Tries to return all the cached roles of the user.
     * If it can't bring the roles from the cache,
     * it brings them back from the DB.
     */
    protected function userCachedRoles(): array
    {
        $data = $this->resolvePermissions();
        return $data["roles"];
    }

    /**
     * Tries to return all the cached groups of the user.
     * If it can't bring the groups from the cache,
     * it brings them back from the DB.
     */
    protected function userCachedGroups(): array
    {
        $data = $this->resolvePermissions();
        return $data["groups"];
    }

    /**
     * Tries to return all the cached permissions of the user
     * and if it can't bring the permissions from the cache,
     * it brings them back from the DB.
     */
    public function userCachedPermissions(): array
    {
        $data = $this->resolvePermissions();
        return $data["permissions"];
    }

    /**
     * Tries return key name for user_models.
     *
     * @return string|void default key user
     */
    public function userModelCacheKey(): string
    {
        foreach (Config::get('laratrust.user_models') as $key => $model) {
            if ($this->user instanceof $model) {
                return $key;
            }
        }

        $modelClass = get_class($this);

        throw new UnexpectedValueException("Class '{$modelClass}' is not defined in the laratrust.user_models");
    }

    // /**
    //  * Creates a model from an array filled with the class data.
    //  */
    // private function hidrateRole(string $class, Model|array $data): Role
    // {
    //     if ($data instanceof Model) {
    //         return $data;
    //     }

    //     if (!isset($data['pivot'])) {
    //         throw new \Exception("The 'pivot' attribute in the {$class} is hidden");
    //     }

    //     $role = new $class;
    //     $primaryKey = $role->getKeyName();

    //     $role
    //         ->setAttribute($primaryKey, $data[$primaryKey])
    //         ->setAttribute('name', $data['name'])
    //         ->setRelation(
    //             'pivot',
    //             MorphPivot::fromRawAttributes($role, $data['pivot'], 'pivot_table')
    //         );

    //     return $role;
    // }

    // /**
    //  * Creates a model from an array filled with the class data.
    //  */
    // private function hidrateGroup(string $class, Model|array $data): Group
    // {
    //     if ($data instanceof Model) {
    //         return $data;
    //     }

    //     if (!isset($data['pivot'])) {
    //         throw new \Exception("The 'pivot' attribute in the {$class} is hidden");
    //     }

    //     $group = new $class;
    //     $primaryKey = $group->getKeyName();

    //     $group
    //         ->setAttribute($primaryKey, $data[$primaryKey])
    //         ->setAttribute('name', $data['name'])
    //         ->setRelation(
    //             'pivot',
    //             MorphPivot::fromRawAttributes($group, $data['pivot'], 'pivot_table')
    //         );

    //     return $group;
    // }

    public function resolvePermissions(): array
    {
        $cacheKey = 'laratrust_cache_for_' . $this->user->getKey();

        if (!Config::get('laratrust.cache.enabled')) {
            return $this->permissionResolver();
        };

        return Cache::remember($cacheKey, Config::get('laratrust.cache.expiration_time', 60), function () {
            return $this->permissionResolver();
        });
    }

    private function permissionResolver(): array
    {
        $groups = $this->user->groups()->get();
        $roles = $this->user->roles()->get();
        $permissions = $this->user->permissions()->get();

        foreach ($groups as $group) {
            $roles = $roles->merge($group->roles()->get());
            $permissions = $permissions->merge($group->permissions()->get());
        }
        foreach ($roles as $role) {
            $permissions = $permissions->merge($role->permissions()->get());
        }

        if (!Config::get('laratrust.cache.enabled')) {
            return [
                'groups' => $groups->unique('id')->toArray(),
                'roles' => $roles->unique('id')->toArray(),
                'permissions' => $permissions->unique('id')->toArray(),
            ];
        }

        return [
            'groups' => array_map(fn ($group) => [
                'id' => $group['id'],
                'name' => $group['name'],
                'display_name' => $group['display_name'],
            ], $groups->unique('id')->toArray()),
            'roles' => array_map(fn ($role) => [
                'id' => $role['id'],
                'name' => $role['name'],
                'display_name' => $role['display_name'],
            ], $roles->unique('id')->toArray()),
            'permissions' => array_map(fn ($permission) => [
                'id' => $permission['id'],
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
            ], $permissions->unique('id')->toArray())
        ];
    }
}
