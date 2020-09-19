<?php


namespace Laratrust\Checkers\PermissionAble;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Laratrust\Helper;
use Laratrust\Traits\LaratrustModelHasPermissions;


/**
 * Class LaratrustUserChecker
 * @property Model|LaratrustModelHasPermissions $model
 */
trait ModelHasPermissionsQueryCheckerTrait
{
    /**
     * @inheritDoc
     */
    public function currentModelHasPermission($permission, $team = null, $requireAll = false, callable $callback = null)
    {
        $permission = Helper::standardize($permission);
        $permissionsNames = is_array($permission) ? $permission : [$permission];

        if (empty($permission)) {
            return true;
        }

        list($permissionsWildcard, $permissionsNoWildcard) =
            Helper::getPermissionWithAndWithoutWildcards($permissionsNames);

        $permissionsCount = $this->model->permissions()
            ->whereIn('name', $permissionsNoWildcard)
            ->when($permissionsWildcard, function ($query) use ($permissionsWildcard) {
                foreach ($permissionsWildcard as $permission) {
                    $query->orWhere('name', 'like', $permission);
                }

                return $query;
            })
            ->count();

        if ($callback) {
            return $callback($permission, $team, $requireAll);
        }

        return $requireAll
            ? $permissionsCount >= count($permissionsNames)
            : $permissionsCount > 0;
    }

    /**
     * @param $permissionsWildcard
     * @param $permissionsNoWildcard
     * @param  null  $team
     * @return int
     */
    protected function directPermissionsCount($permissionsWildcard, $permissionsNoWildcard, $team = null)
    {
        $useTeams = Config::get('laratrust.teams.enabled');
        $teamStrictCheck = Config::get('laratrust.teams.strict_check');
        return $this->model->permissions()
            ->whereIn('name', $permissionsNoWildcard)
            ->when($permissionsWildcard, function ($query) use ($permissionsWildcard) {
                foreach ($permissionsWildcard as $permission) {
                    $query->orWhere('name', 'like', $permission);
                }

                return $query;
            })
            ->when($useTeams && ($teamStrictCheck || !is_null($team)), function ($query) use ($team) {
                $teamId = Helper::fetchTeam($team);

                return $query->where(Config::get('laratrust.foreign_keys.team'), $teamId);
            })
            ->count();
    }

}
