<?php


namespace Laratrust\Checkers\PermissionAble;


use Illuminate\Database\Eloquent\Builder;
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

        $permissionsCount = $this->directPermissionsCount($permissionsWildcard, $permissionsNoWildcard, $team);

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
        $query = $this->model->permissions()
            ->whereIn('name', $permissionsNoWildcard)
            ->when($permissionsWildcard, function ($query) use ($permissionsWildcard) {
                foreach ($permissionsWildcard as $permission) {
                    $query->orWhere('name', 'like', $permission);
                }

                return $query;
            });

        return $this->queryTeam($query, $useTeams, $teamStrictCheck, $team)->count();
    }


    /**
     * @param  string  $relation
     * @param  array  $permissionsNoWildcard
     * @param  array  $permissionsWildcard
     * @param $useTeams
     * @param $teamStrictCheck
     * @param $team
     * @return int
     */
    protected function getPermissionsCountThroughRelation(string $relation, array $permissionsNoWildcard, array $permissionsWildcard, $useTeams, $teamStrictCheck, $team = null)
    {
        $query = $this->model->{$relation}()
            ->withCount([
                'permissions' =>
                    function ($query) use ($permissionsNoWildcard, $permissionsWildcard) {
                        $query->whereIn('name', $permissionsNoWildcard);
                        foreach ($permissionsWildcard as $permission) {
                            $query->orWhere('name', 'like', $permission);
                        }
                    }
            ]);

        return $this->queryTeam($query, $useTeams, $teamStrictCheck, $team)
            ->pluck('permissions_count')
            ->sum();

    }

    /**
     *  * $team param can be used with the value of false to ignore teams
     *
     * @param   $query
     * @param $useTeams
     * @param $teamStrictCheck
     * @param  null | false |mixed  $team
     * @return \Illuminate\Database\Concerns\BuildsQueries|Builder|mixed
     */
    protected function queryTeam( $query, $useTeams, $teamStrictCheck, $team = null)
    {
        $includeQuery = $useTeams && ($teamStrictCheck || !is_null($team)) && $team !== false;
        return $query->when($includeQuery, function ($query) use ($team) {
            $teamId = Helper::fetchTeam($team);

            return $query->where(Config::get('laratrust.foreign_keys.team'), $teamId);
        });
    }

}
