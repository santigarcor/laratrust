<?php

namespace Laratrust\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laratrust\Helper;

/**
 * Trait LaratrustHasPermissionsScopes
 *
 * @method static Builder wherePermissionIs($permission = '', $boolean = 'and')
 * @method static Builder orWherePermissionIs($permission = '')
 * @method static Builder whereDoesntHavePermission()
 * @method static Builder whereRelatedTeamIs($team = null)
 * @method static Builder whereTeamIs($team = null)
 */
trait LaratrustHasPermissionsScopes
{

    /**
     * This scope allows to retrieve the users with a specific permission.
     * @param  Builder  $query
     * @param  string  $permission
     * @return Builder
     */
    public function scopeWherePermissionIs($query, $permission = '', $boolean = 'and')
    {
        $method = $boolean == 'and' ? 'where' : 'orWhere';

        return $query->$method(function ($query) use ($permission) {
            $method = is_array($permission) ? 'whereIn' : 'where';

            $query->whereHas('roles.permissions', function ($permissionQuery) use ($method, $permission) {
                $permissionQuery->$method('name', $permission);
            })->orWhereHas('permissions', function ($permissionQuery) use ($method, $permission) {
                $permissionQuery->$method('name', $permission);
            });
        });
    }


    /**
     * /**
     * This scope allows to retrieve relation with on a specific team.
     * $team param can be used with the value of false to ignore teams
     *
     * @param  Builder  $query
     * @param  null|string|false  $team
     * @return Builder
     */
    public function scopeWhereRelatedTeamIs(Builder $query, $team = null)
    {
        return $query->when(config('laratrust.teams.enabled') && $team !== false, function ($query) use ($team) {

            return $query->where(config('laratrust.foreign_keys.team'), Helper::getIdFor($team, 'team'));
        });

    }

    /**
     * /**
     * This scope allows to retrieve restrict query on a specific team.
     * $team param can be used with the value of false to ignore teams
     *
     * @param  MorphToMany  $query
     * @param  null|string|false  $team
     * @return MorphToMany
     */
    public function scopeWhereTeamIs(MorphToMany $query, $team = null)
    {
        if (config('laratrust.teams.enabled') && $team !== false) {
            return $query->wherePivot(config('laratrust.foreign_keys.team'), Helper::getIdFor($team, 'team'));
        }
        return $query;
    }

    /**
     * This scope allows to retrive the users with a specific permission.
     *
     * @param  Builder  $query
     * @param  string  $permission
     * @return Builder
     */
    public function scopeOrWherePermissionIs($query, $permission = '')
    {
        return $this->scopeWherePermissionIs($query, $permission, 'or');
    }


    /**
     * Filter by the users that don't have permissions assigned.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeWhereDoesntHavePermission($query)
    {
        return $query->where(function ($query) {
            $query->doesntHave('permissions')
                ->orDoesntHave('roles.permissions');
        });
    }
}
