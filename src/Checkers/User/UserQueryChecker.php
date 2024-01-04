<?php

declare(strict_types=1);

namespace Laratrust\Checkers\User;

use BackedEnum;
use Illuminate\Database\Eloquent\Collection;
use Laratrust\Helper;

class UserQueryChecker extends UserChecker
{

    public function getCurrentUserRoles(): array
    {
        return $this->user->roles->pluck('name')->toArray();
    }

    public function getCurrentUserGroups(): array
    {
        return $this->user->groups->pluck('name')->toArray();
    }

    public function getCurrentUserPermissions(): array
    {
        return $this->user->permissions->pluck('name')->toArray();
    }

    public function currentUserHasRole(
        string|array|BackedEnum $name,
        bool $requireAll = false
    ): bool {
        if (empty($name)) {
            return true;
        }

        $name = Helper::standardize($name);
        $rolesNames = is_array($name) ? $name : [$name];

        $rolesCount = $this->user->roles()
            ->whereIn('name', $rolesNames)
            ->count();

        return $requireAll ? $rolesCount == count($rolesNames) : $rolesCount > 0;
    }

    public function currentUserHasGroup(
        string|array|BackedEnum $name,
        bool $requireAll = false
    ): bool {
        if (empty($name)) {
            return true;
        }

        $name = Helper::standardize($name);
        $groupsNames = is_array($name) ? $name : [$name];

        $groupsCount = $this->user->groups()
            ->whereIn('name', $groupsNames)
            ->count();

        return $requireAll ? $groupsCount == count($groupsNames) : $groupsCount > 0;
    }

    public function currentUserHasPermission(
        string|array|BackedEnum $permission,
        bool $requireAll = false
    ): bool {
        if (empty($permission)) {
            return true;
        }

        $permission = Helper::standardize($permission);
        $permissionsNames = is_array($permission) ? $permission : [$permission];

        [$permissionsWildcard, $permissionsNoWildcard] =
            Helper::getPermissionWithAndWithoutWildcards($permissionsNames);

        $rolesPermissionsCount = $this->user->roles()
            ->withCount([
                'permissions' => function ($query) use ($permissionsNoWildcard, $permissionsWildcard) {
                    $query->whereIn('name', $permissionsNoWildcard);
                    foreach ($permissionsWildcard as $permission) {
                        $query->orWhere('name', 'like', $permission);
                    }
                },
            ])
            ->pluck('permissions_count')
            ->sum();

        $directPermissionsCount = $this->user->permissions()
            ->whereIn('name', $permissionsNoWildcard)
            ->when($permissionsWildcard, function ($query) use ($permissionsWildcard) {
                foreach ($permissionsWildcard as $permission) {
                    $query->orWhere('name', 'like', $permission);
                }

                return $query;
            })
            ->count();

        return $requireAll
            ? $rolesPermissionsCount + $directPermissionsCount >= count($permissionsNames)
            : $rolesPermissionsCount + $directPermissionsCount > 0;
    }

    public function currentUserFlushCache(bool $recreate = true)
    {
    }

    public function resolvePermissions(): array
    {
        return $this->permissionResolver();
    }

    private function permissionResolver(): array
    {
        $groups = $this->user->groups()->get();
        $roles = $this->user->roles()->get();
        $permissions = new Collection();

        foreach ($groups as $group) {
            $roles = $roles->merge($group->roles()->get());
            $permissions = $permissions->merge($group->permissions()->get());
        }
        foreach ($roles as $role) {
            $permissions = $permissions->merge($role->permissions()->get());
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
