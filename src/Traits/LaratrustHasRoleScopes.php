<?php

namespace Laratrust\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Laratrust\Helper;

/**
 * Trait LaratrustHasRoleScopes
 *
 * @method static Builder whereRoleIs($role = '', $team = null, $boolean = 'and')
 * @method static Builder orWhereRoleIs($role = '', $team = null)
 * @method static Builder whereDoesntHaveRole()
 */
trait LaratrustHasRoleScopes
{
    /**
     * This scope allows to retrive the users with a specific role.
     *
     * @param  Builder  $query
     * @param  string  $role
     * @param  string  $boolean
     * @return Builder
     */
    public function scopeWhereRoleIs($query, $role = '', $team = null, $boolean = 'and')
    {
        $method = $boolean == 'and' ? 'whereHas' : 'orWhereHas';

        return $query->$method('roles', function ($roleQuery) use ($role, $team) {
            $teamsStrictCheck = Config::get('laratrust.teams.strict_check');
            $method = is_array($role) ? 'whereIn' : 'where';

            $roleQuery->$method('name', $role)
                ->when($team || $teamsStrictCheck, function ($query) use ($team) {
                    $team = Helper::getIdFor($team, 'team');
                    return $query->where(Helper::teamForeignKey(), $team);
                });
        });
    }

    /**
     * This scope allows to retrive the users with a specific role.
     *
     * @param  Builder  $query
     * @param  string  $role
     * @return Builder
     */
    public function scopeOrWhereRoleIs($query, $role = '', $team = null)
    {
        return $this->scopeWhereRoleIs($query, $role, $team, 'or');
    }


    /**
     * Filter by the users that don't have roles assigned.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeWhereDoesntHaveRole($query)
    {
        return $query->doesntHave('roles');
    }


}
