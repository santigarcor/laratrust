<?php

namespace Laratrust\Checkers\User;

use Illuminate\Support\Facades\Config;
use Laratrust\Checkers\PermissionAble\ModelHasPermissionsQueryCheckerTrait;
use Laratrust\Helper;

class LaratrustUserQueryChecker extends LaratrustUserChecker
{
    use ModelHasPermissionsQueryCheckerTrait {
        currentModelHasPermission as baseModelHAsPermission;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentUserRoles($team = null)
    {
        if (config('laratrust.teams.enabled') === false) {
            return $this->model->roles->pluck('name')->toArray();
        }

        if ($team === null && config('laratrust.teams.strict_check') === false) {
            return $this->model->roles->pluck('name')->toArray();
        }

        if ($team === null) {
            return $this->model
                ->roles()
                ->wherePivot(config('laratrust.foreign_keys.team'), null)
                ->pluck('name')
                ->toArray();
        }

        $teamId = Helper::fetchTeam($team);

        return $this->model
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

        $rolesCount = $this->model->roles()
            ->whereIn('name', $rolesNames)
            ->when($useTeams && ($teamStrictCheck || !is_null($team)), function ($query) use ($team) {
                $teamId = Helper::fetchTeam($team);

                return $query->where(Config::get('laratrust.foreign_keys.team'), $teamId);
            })
            ->count();

        return $requireAll ? $rolesCount == count($rolesNames) : $rolesCount > 0;
    }

    /**
     * @inheritDoc
     */
    public function currentModelHasPermission($permission, $team = null, $requireAll = false, callable $callback = null)
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

        $rolesPermissionsCount = $this->getPermissionsCountThroughRelation('roles', $permissionsNoWildcard, $permissionsWildcard, $useTeams, $teamStrictCheck, $team);

        $teamsPermissionsCount = 0;
        if ($useTeams) {
            $teamsPermissionsCount = $this->getPermissionsCountThroughRelation('rolesTeams', $permissionsNoWildcard, $permissionsWildcard, $useTeams, $teamStrictCheck, $team);
        }

        $directPermissionsCount = $this->directPermissionsCount($permissionsWildcard, $permissionsNoWildcard, $team);


        $allPermissionsCount = $rolesPermissionsCount + $directPermissionsCount + $teamsPermissionsCount;
        return $requireAll
            ? $allPermissionsCount >= count($permissionsNames)
            : $allPermissionsCount > 0;
    }


}
