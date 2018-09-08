<?php

namespace Laratrust\Checkers\User;

use Laratrust\Helper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class LaratrustUserQueryChecker extends LaratrustUserChecker
{
    /**
     * Checks if the user has a role by its name.
     *
     * @param  string|array  $name       Role name or array of role names.
     * @param  string|bool   $team      Team name or requiredAll roles.
     * @param  bool          $requireAll All roles in the array are required.
     * @return bool
     */
    public function currentUserHasRole($name, $team = null, $requireAll = false)
    {
        if (empty($name)) {
            return true;
        }

        $name = Helper::standardize($name);
        $rolesNames = is_array($name) ? $name : [$name];
        list($team, $requireAll) = Helper::assignRealValuesTo($team, $requireAll, 'is_bool');
        $useTeams = Config::get('laratrust.use_teams');
        $teamStrictCheck = Config::get('laratrust.teams_strict_check');

        $rolesCount = $this->user->roles()
            ->whereIn('name', $rolesNames)
            ->when($useTeams && ($teamStrictCheck || !is_null($team)), function ($query) use ($team) {
                $teamId = Helper::fetchTeam($team);

                return $query->where(Config::get('laratrust.foreign_keys.team'), $teamId);
            })
            ->count();

        return $requireAll ? $rolesCount == count($rolesNames) : $rolesCount > 0;
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param  string|array  $permission Permission string or array of permissions.
     * @param  string|bool  $team      Team name or requiredAll roles.
     * @param  bool  $requireAll All roles in the array are required.
     * @return bool
     */
    public function currentUserHasPermission($permission, $team = null, $requireAll = false)
    {
        if (empty($permission)) {
            return true;
        }

        $permission = Helper::standardize($permission);
        $permissionsNames = is_array($permission) ? $permission : [$permission];
        list($team, $requireAll) = Helper::assignRealValuesTo($team, $requireAll, 'is_bool');
        $useTeams = Config::get('laratrust.use_teams');
        $teamStrictCheck = Config::get('laratrust.teams_strict_check');

        $rolesPermissionsCount = $this->user->roles()
            ->withCount(['permissions' => function ($query) use ($permissionsNames) {
                $query->whereIn('name', $permissionsNames);
            }])
            ->when($useTeams && ($teamStrictCheck || !is_null($team)), function ($query) use ($team) {
                $teamId = Helper::fetchTeam($team);

                return $query->where(Config::get('laratrust.foreign_keys.team'), $teamId);
            })
            ->pluck('permissions_count')
            ->sum();

        $directPermissionsCount = $this->user->permissions()
            ->whereIn('name', $permissionsNames)
            ->when($useTeams && ($teamStrictCheck || !is_null($team)), function ($query) use ($team) {
                $teamId = Helper::fetchTeam($team);

                return $query->where(Config::get('laratrust.foreign_keys.team'), $teamId);
            })
            ->count();


        return $requireAll
            ? $rolesPermissionsCount + $directPermissionsCount >= count($permissionsNames)
            : $rolesPermissionsCount + $directPermissionsCount > 0;
    }

    /**
     * Checks role(s) and permission(s).
     *
     * @param  string|array  $roles       Array of roles or comma separated string
     * @param  string|array  $permissions Array of permissions or comma separated string.
     * @param  string|bool  $team      Team name or requiredAll roles.
     * @param  array  $options     validate_all (true|false) or return_type (boolean|array|both)
     * @throws \InvalidArgumentException
     * @return array|bool
     */
    public function currentUserHasAbility($roles, $permissions, $team = null, $options = [])
    {
        list($team, $options) = Helper::assignRealValuesTo($team, $options, 'is_array');
        // Convert string to array if that's what is passed in.
        $roles = Helper::standardize($roles, true);
        $permissions = Helper::standardize($permissions, true);

        // Set up default values and validate options.
        $options = Helper::checkOrSet('validate_all', $options, [false, true]);
        $options = Helper::checkOrSet('return_type', $options, ['boolean', 'array', 'both']);

        // Loop through roles and permissions and check each.
        $checkedRoles = [];
        $checkedPermissions = [];
        foreach ($roles as $role) {
            $checkedRoles[$role] = $this->currentUserHasRole($role, $team);
        }
        foreach ($permissions as $permission) {
            $checkedPermissions[$permission] = $this->currentUserHasPermission($permission, $team);
        }

        // If validate all and there is a false in either.
        // Check that if validate all, then there should not be any false.
        // Check that if not validate all, there must be at least one true.
        if (($options['validate_all'] && !(in_array(false, $checkedRoles) || in_array(false, $checkedPermissions))) || (!$options['validate_all'] && (in_array(true, $checkedRoles) || in_array(true, $checkedPermissions)))) {
            $validateAll = true;
        } else {
            $validateAll = false;
        }

        // Return based on option.
        if ($options['return_type'] == 'boolean') {
            return $validateAll;
        } elseif ($options['return_type'] == 'array') {
            return ['roles' => $checkedRoles, 'permissions' => $checkedPermissions];
        }

        return [$validateAll, ['roles' => $checkedRoles, 'permissions' => $checkedPermissions]];
    }

    public function currentUserFlushCache()
    {
        Cache::forget('laratrust_roles_for_user_' . $this->user->getKey());
        Cache::forget('laratrust_permissions_for_user_' . $this->user->getKey());
    }

    /**
     * Tries to return all the cached roles of the user.
     * If it can't bring the roles from the cache,
     * it brings them back from the DB.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function userCachedRoles()
    {
        $cacheKey = 'laratrust_roles_for_user_' . $this->user->getKey();

        if (!Config::get('laratrust.use_cache')) {
            return $this->user->roles()->get();
        }

        return Cache::remember($cacheKey, Config::get('cache.ttl', 60), function () {
            return $this->user->roles()->get()->toArray();
        });
    }

    /**
     * Tries to return all the cached permissions of the user
     * and if it can't bring the permissions from the cache,
     * it brings them back from the DB.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function userCachedPermissions()
    {
        $cacheKey = 'laratrust_permissions_for_user_' . $this->user->getKey();

        if (!Config::get('laratrust.use_cache')) {
            return $this->user->permissions()->get();
        }

        return Cache::remember($cacheKey, Config::get('cache.ttl', 60), function () {
            return $this->user->permissions()->get()->toArray();
        });
    }
}
