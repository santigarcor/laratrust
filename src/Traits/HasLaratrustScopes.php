<?php

declare(strict_types=1);

namespace Laratrust\Traits;

use BackedEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Config;
use Laratrust\Helper;
use Laratrust\Models\Team;

/**
 * @method static Builder<static> whereHasRole(string|array|BackedEnum $role = '', mixed $team = null, string $boolean = 'and')
 * @method static Builder<static> orWhereHasRole(string|array|BackedEnum $role = '', mixed $team = null)
 * @method static Builder<static> whereHasPermission(string|array|BackedEnum $permission = '', mixed $team = null, string $boolean = 'and')
 * @method static Builder<static> orWhereHasPermission(string|array|BackedEnum $permission = '', mixed $team = null)
 * @method static Builder<static> whereDoesntHaveRoles()
 * @method static Builder<static> whereDoesntHavePermissions()
 */
trait HasLaratrustScopes
{
    /**
     * This scope allows to retrive the users with a specific role.
     */
    #[Scope]
    protected function whereHasRole(
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
    #[Scope]
    protected function orWhereHasRole(
        Builder $query,
        string|array|BackedEnum $role = '',
        mixed $team = null
    ): Builder {
        return $this->whereHasRole($query, $role, $team, 'or');
    }

    /**
     * This scope allows to retrieve the users with a specific permission.
     */
    #[Scope]
    protected function whereHasPermission(
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
    #[Scope]
    protected function orWhereHasPermission(
        Builder $query,
        string|array|BackedEnum $permission = '',
        mixed $team = null
    ): Builder {
        return $this->whereHasPermission($query, $permission, $team, 'or');
    }

    /**
     * Filter by the users that don't have roles assigned.
     */
    #[Scope]
    protected function whereDoesntHaveRoles(Builder $query): Builder
    {
        return $query->doesntHave('roles');
    }

    /**
     * Filter by the users that don't have permissions assigned.
     */
    #[Scope]
    protected function whereDoesntHavePermissions(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->doesntHave('permissions')
                ->orDoesntHave('roles.permissions');
        });
    }
}
