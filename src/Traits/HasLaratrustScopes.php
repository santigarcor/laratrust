<?php

declare(strict_types=1);

namespace Laratrust\Traits;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Laratrust\Helper;
use Laratrust\Models\Team;

/**
 * @method Builder whereHasRole(string|array|BackedEnum $role = '', mixed $team = null, string $boolean = 'and')
 * @method Builder orWhereHasRole(string|array|BackedEnum $role = '', mixed $team = null)
 * @method Builder whereHasPermission(string|array|BackedEnum $permission = '', string $boolean = 'and')
 * @method Builder orWhereHasPermission(string|array|BackedEnum $permission = '')
 * @method Builder whereDoesntHaveRoles()
 * @method Builder whereDoesntHavePermissions()
 * @method static Builder whereHasRole(string|array|BackedEnum $role = '', mixed $team = null, string $boolean = 'and')
 * @method static Builder orWhereHasRole(string|array|BackedEnum $role = '', mixed $team = null)
 * @method static Builder whereHasPermission(string|array|BackedEnum $permission = '', string $boolean = 'and')
 * @method static Builder orWhereHasPermission(string|array|BackedEnum $permission = '')
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
        string|array|BackedEnum $role = '',
        mixed $team = null,
        string $boolean = 'and'
    ): Builder {
        $method = $boolean == 'and' ? 'whereHas' : 'orWhereHas';

        return $query->$method('roles', function ($roleQuery) use ($role, $team) {
            $teamsStrictCheck = Config::get('laratrust.teams.strict_check');
            $method = is_array($role) ? 'whereIn' : 'where';

            $roleQuery
                ->$method('name', $role)
                ->when(
                    $team || $teamsStrictCheck,
                    fn ($q) => $q->where(
                        Team::modelForeignKey(),
                        Helper::getIdFor($team, 'team')
                    )
                );
        });
    }

    /**
     * This scope allows to retrive the users with a specific role.
     */
    public function scopeOrWhereHasRole(
        Builder $query,
        string|array|BackedEnum $role = '',
        mixed $team = null
    ): Builder {
        return $this->scopeWhereHasRole($query, $role, $team, 'or');
    }

    /**
     * This scope allows to retrieve the users with a specific permission.
     */
    public function scopeWhereHasPermission(
        Builder $query,
        string|array|BackedEnum $permission = '',
        mixed $team = null,
        string $boolean = 'and'
    ): Builder {
        $method = $boolean == 'and' ? 'where' : 'orWhere';

        return $query->$method(function ($query) use ($permission, $team) {
            $teamsStrictCheck = Config::get('laratrust.teams.strict_check');
            $method = is_array($permission) ? 'whereIn' : 'where';

            $query
            ->whereHas(
                'roles.permissions',
                fn ($permissionQuery) => $permissionQuery
                    ->$method('name', $permission)
                    ->when(
                        $team || $teamsStrictCheck,
                        fn ($q) => $q->where(
                            Team::modelForeignKey(),
                            Helper::getIdFor($team, 'team')
                        )
                    )
            )
            ->orWhereHas(
                'permissions',
                fn ($permissionQuery) => $permissionQuery
                    ->$method('name', $permission)
                    ->when(
                        $team || $teamsStrictCheck,
                        fn ($q) => $q->where(
                            Team::modelForeignKey(),
                            Helper::getIdFor($team, 'team')
                        )
                    )
            );
        });
    }

    /**
     * This scope allows to retrive the users with a specific permission.
     */
    public function scopeOrWhereHasPermission(
        Builder $query,
        string|array|BackedEnum $permission = '',
        mixed $team = null
    ): Builder {
        return $this->scopeWhereHasPermission($query, $permission, $team, 'or');
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
