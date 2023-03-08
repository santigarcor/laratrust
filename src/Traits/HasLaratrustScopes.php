<?php

declare(strict_types=1);

namespace Laratrust\Traits;

use Laratrust\Helper;
use Laratrust\Models\Team;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

/**
 * @method Builder whereHasRole(string|array $role = '', mixed $team = null, string $boolean = 'and')
 * @method Builder orWhereHasRole(string|array $role = '', mixed $team = null)
 * @method Builder whereHasPermission(string|array $permission = '', string $boolean = 'and')
 * @method Builder orWhereHasPermission(string|array $permission = '')
 * @method Builder whereDoesntHaveRoles()
 * @method Builder whereDoesntHavePermissions()
 * @method static Builder whereHasRole(string|array $role = '', mixed $team = null, string $boolean = 'and')
 * @method static Builder orWhereHasRole(string|array $role = '', mixed $team = null)
 * @method static Builder whereHasPermission(string|array $permission = '', string $boolean = 'and')
 * @method static Builder orWhereHasPermission(string|array $permission = '')
 * @method static Builder whereDoesntHaveRoles()
 * @method static Builder whereDoesntHavePermissions()
 */
trait HasLaratrustScopes
{
    /**
     * This scope allows to retrive the users with a specific role.
     */
    public function scopeWhereHasRole(
        Builder $query,
        string|array $role = '',
        mixed $team = null,
        string $boolean = 'and'
    ): Builder {
        $method = $boolean == 'and' ? 'whereHas' : 'orWhereHas';

        return $query->$method('roles', function ($roleQuery) use ($role, $team) {
            $teamsStrictCheck = Config::get('laratrust.teams.strict_check');
            $method = is_array($role) ? 'whereIn' : 'where';

            $roleQuery->$method('name', $role)
                ->when($team || $teamsStrictCheck, function ($query) use ($team) {
                    $team = Helper::getIdFor($team, 'team');
                    return $query->where(Team::modelForeignKey(), $team);
                });
        });
    }

    /**
     * This scope allows to retrive the users with a specific role.
     */
    public function scopeOrWhereHasRole(
        Builder $query,
        string|array $role = '',
        mixed $team = null
    ): Builder {
        return $this->scopeWhereHasRole($query, $role, $team, 'or');
    }

    /**
     * This scope allows to retrieve the users with a specific permission.
     */
    public function scopeWhereHasPermission(
        Builder $query,
        string|array $permission = '',
        string $boolean = 'and'
    ): Builder {
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
     * This scope allows to retrive the users with a specific permission.
     */
    public function scopeOrWhereHasPermission(
        Builder $query,
        string|array $permission = ''
    ): Builder {
        return $this->scopeWhereHasPermission($query, $permission, 'or');
    }

    /**
     * Filter by the users that don't have roles assigned.
     */
    public function scopeWhereDoesntHaveRoles(Builder $query): Builder
    {
        return $query->doesntHave('roles');
    }

    /**
     * Filter by the users that don't have permissions assigned.
     */
    public function scopeWhereDoesntHavePermissions(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->doesntHave('permissions')
                ->orDoesntHave('roles.permissions');
        });
    }
}
