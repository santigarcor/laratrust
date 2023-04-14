<?php

declare(strict_types=1);

namespace Laratrust\Checkers\User;

use BackedEnum;
use Illuminate\Support\Facades\Config;
use Laratrust\Helper;

class UserQueryChecker extends UserChecker
{
    public function getCurrentUserRoles(mixed $team = null): array
    {
        if (config('laratrust.teams.enabled') === false) {
            return $this->user->roles->pluck('name')->toArray();
        }

        if ($team === null && config('laratrust.teams.strict_check') === false) {
            return $this->user->roles->pluck('name')->toArray();
        }

        $teamId = Helper::getIdFor($team, 'team');

        return $this->user
            ->roles()
            ->wherePivot(config('laratrust.foreign_keys.team'), $teamId)
            ->pluck('name')
            ->toArray();
    }

    public function currentUserHasRole(
        string|array|BackedEnum $name,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        if (empty($name)) {
            return true;
        }

        $name = Helper::standardize($name);
        $rolesNames = is_array($name) ? $name : [$name];
        [
            'team' => $team,
            'require_all' => $requireAll
        ] = $this->getRealValues($team, $requireAll, 'is_bool');
        $useTeams = Config::get('laratrust.teams.enabled');
        $teamStrictCheck = Config::get('laratrust.teams.strict_check');

        $rolesCount = $this->user->roles()
            ->whereIn('name', $rolesNames)
            ->when($useTeams && ($teamStrictCheck || ! is_null($team)), function ($query) use ($team) {
                $teamId = Helper::getIdFor($team, 'team');

                return $query->where(Config::get('laratrust.foreign_keys.team'), $teamId);
            })
            ->count();

        return $requireAll ? $rolesCount == count($rolesNames) : $rolesCount > 0;
    }

    public function currentUserHasPermission(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        if (empty($permission)) {
            return true;
        }

        $permission = Helper::standardize($permission);
        $permissionsNames = is_array($permission) ? $permission : [$permission];
        [
            'team' => $team,
            'require_all' => $requireAll
        ] = $this->getRealValues($team, $requireAll, 'is_bool');
        $useTeams = Config::get('laratrust.teams.enabled');
        $teamStrictCheck = Config::get('laratrust.teams.strict_check');

        [$permissionsWildcard, $permissionsNoWildcard] =
            Helper::getPermissionWithAndWithoutWildcards($permissionsNames);

        $rolesPermissionsCount = $this->user->roles()
            ->withCount(['permissions' => function ($query) use ($permissionsNoWildcard, $permissionsWildcard) {
                $query->whereIn('name', $permissionsNoWildcard);
                foreach ($permissionsWildcard as $permission) {
                    $query->orWhere('name', 'like', $permission);
                }
            },
            ])
            ->when($useTeams && ($teamStrictCheck || ! is_null($team)), function ($query) use ($team) {
                $teamId = Helper::getIdFor($team, 'team');

                return $query->where(Config::get('laratrust.foreign_keys.team'), $teamId);
            })
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
            ->when($useTeams && ($teamStrictCheck || ! is_null($team)), function ($query) use ($team) {
                $teamId = Helper::getIdFor($team, 'team');

                return $query->where(Config::get('laratrust.foreign_keys.team'), $teamId);
            })
            ->count();

        return $requireAll
            ? $rolesPermissionsCount + $directPermissionsCount >= count($permissionsNames)
            : $rolesPermissionsCount + $directPermissionsCount > 0;
    }

    public function currentUserFlushCache()
    {
    }
}
