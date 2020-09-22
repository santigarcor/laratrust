<?php

namespace Laratrust\Checkers\User;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Laratrust\Helper;

class LaratrustUserQueryChecker extends LaratrustUserChecker
{
    /**
     * @inheritDoc
     */
    public function getCurrentUserRoles($team = null)
    {
        if (config('laratrust.teams.enabled') === false) {
            return $this->user->roles->pluck('name')->toArray();
        }

        if ($team === null && config('laratrust.teams.strict_check') === false) {
            return $this->user->roles->pluck('name')->toArray();
        }


        $teamId = $team ? Helper::fetchTeam($team) : null;

        return $this->user
            ->roles()
            ->wherePivot(config('laratrust.foreign_keys.team'), $teamId)
            ->pluck('name')
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    public function currentUserHasRole($name, $team = null, $requireAll = false)
    {
        if (empty($name)) {
            return true;
        }

        $name = Helper::standardize($name);
        $rolesNames = is_array($name) ? $name : [$name];
        list($team, $requireAll) = Helper::assignRealValuesTo($team, $requireAll, 'is_bool');
        $useTeams = Config::get('laratrust.teams.enabled');
        $teamStrictCheck = Config::get('laratrust.teams.strict_check');

        $query = $this->user->roles()->whereIn('name', $rolesNames);

        $rolesCount = $this->queryTeam($query, $useTeams, $teamStrictCheck, $team)
            ->count();

        return $requireAll ? $rolesCount == count($rolesNames) : $rolesCount > 0;
    }

    /**
     * @inheritDoc
     */
    public function currentUserHasPermission($permission, $team = null, $requireAll = false)
    {
        if (empty($permission)) {
            return true;
        }

        $permission = Helper::standardize($permission);
        $permissionsNames = is_array($permission) ? $permission : [$permission];
        list($team, $requireAll) = Helper::assignRealValuesTo($team, $requireAll, 'is_bool');
        $useTeams = Config::get('laratrust.teams.enabled');
        $teamStrictCheck = Config::get('laratrust.teams.strict_check');

        list($permissionsWildcard, $permissionsNoWildcard) =
            Helper::getPermissionWithAndWithoutWildcards($permissionsNames);

        $queryRoles = $this->user->roles()
            ->withCount(['permissions' =>
                function ($query) use ($permissionsNoWildcard, $permissionsWildcard) {
                    $query->whereIn('name', $permissionsNoWildcard);
                    foreach ($permissionsWildcard as $permission) {
                        $query->orWhere('name', 'like', $permission);
                    }
                }
            ]);

        $rolesPermissionsCount=$this->queryTeam($queryRoles, $useTeams, $teamStrictCheck, $team)
            ->pluck('permissions_count')
            ->sum();


        $queryPermissions = $this->user->permissions()
            ->whereIn('name', $permissionsNoWildcard)
            ->when($permissionsWildcard, function ($query) use ($permissionsWildcard) {
                foreach ($permissionsWildcard as $permission) {
                    $query->orWhere('name', 'like', $permission);
                }

                return $query;
            });
        $directPermissionsCount = $this->queryTeam($queryPermissions, $useTeams, $teamStrictCheck, $team)
            ->count();

        return $requireAll
            ? $rolesPermissionsCount + $directPermissionsCount >= count($permissionsNames)
            : $rolesPermissionsCount + $directPermissionsCount > 0;
    }

    /**
     * @param   $query
     * @param $useTeams
     * @param $teamStrictCheck
     * @param  null  |mixed  $team
     * @return \Illuminate\Database\Concerns\BuildsQueries|Builder|mixed
     */
    protected function queryTeam( $query, $useTeams, $teamStrictCheck, $team = null)
    {
        $includeQuery = $useTeams && ($teamStrictCheck || !is_null($team));
        return $query->when($includeQuery, function ($query) use ($team) {
            $teamId = Helper::fetchTeam($team);

            return $query->where(Config::get('laratrust.foreign_keys.team'), $teamId);
        });
    }

    /**
     * @inheritDoc
     */
    public function currentUserFlushCache()
    {
    }
}
