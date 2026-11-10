<?php

declare(strict_types=1);

namespace Laratrust\Traits;

use BackedEnum;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Laratrust\Support\QueryConstraints;

/**
 * @method static Builder<static> whereHasRole(string|array<string|BackedEnum>|BackedEnum $role = '', mixed $team = null, string $boolean = 'and')
 * @method static Builder<static> orWhereHasRole(string|array<string|BackedEnum>|BackedEnum $role = '', mixed $team = null)
 * @method static Builder<static> whereHasPermission(string|array<string|BackedEnum>|BackedEnum $permission = '', mixed $team = null, string $boolean = 'and')
 * @method static Builder<static> orWhereHasPermission(string|array<string|BackedEnum>|BackedEnum $permission = '', mixed $team = null)
 * @method static Builder<static> whereDoesntHaveRoles()
 * @method static Builder<static> whereDoesntHavePermissions()
 */
trait HasLaratrustScopes
{
    /**
     * Get the users with the given role(s).
     *
     * @param  Builder<static>  $query
     * @param  string|array<string|BackedEnum>|BackedEnum  $role
     * @return Builder<static>
     */
    #[Scope]
    protected function whereHasRole(
        Builder $query,
        string|array|BackedEnum $role = '',
        mixed $team = null,
        string $boolean = 'and'
    ): Builder {
        $roles = $this->roles();

        return $query->has(
            'roles',
            boolean: $boolean,
            callback: fn (Builder $roleQuery): Builder => QueryConstraints::constrainToTeam(
                QueryConstraints::whereNameIs($roleQuery, $role),
                $roles,
                $team,
            )
        );
    }

    /**
     * Get the users with the given role(s), as an "or" condition.
     *
     * @param  Builder<static>  $query
     * @param  string|array<string|BackedEnum>|BackedEnum  $role
     * @return Builder<static>
     */
    #[Scope]
    protected function orWhereHasRole(
        Builder $query,
        string|array|BackedEnum $role = '',
        mixed $team = null
    ): Builder {
        return $query->whereHasRole($role, $team, 'or');
    }

    /**
     * Get the users with the given permission(s), given directly or through their roles.
     *
     * @param  Builder<static>  $query
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     * @return Builder<static>
     */
    #[Scope]
    protected function whereHasPermission(
        Builder $query,
        string|array|BackedEnum $permission = '',
        mixed $team = null,
        string $boolean = 'and'
    ): Builder {
        $roles = $this->roles();
        $permissions = $this->permissions();

        return $query->where(fn (Builder $query) => $query
            // Through a role: the team is the one the role was assigned in.
            ->whereHas(
                'roles',
                fn (Builder $roleQuery) => QueryConstraints::constrainToTeam($roleQuery, $roles, $team)
                    ->whereHas('permissions', fn (Builder $permissionQuery): Builder => QueryConstraints::whereNameIs($permissionQuery, $permission))
            )
            // Directly: the team is the one the permission was given in.
            ->orWhereHas(
                'permissions',
                fn (Builder $permissionQuery): Builder => QueryConstraints::constrainToTeam(
                    QueryConstraints::whereNameIs($permissionQuery, $permission),
                    $permissions,
                    $team,
                )
            ), boolean: $boolean);
    }

    /**
     * Get the users with the given permission(s), as an "or" condition.
     *
     * @param  Builder<static>  $query
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     * @return Builder<static>
     */
    #[Scope]
    protected function orWhereHasPermission(
        Builder $query,
        string|array|BackedEnum $permission = '',
        mixed $team = null
    ): Builder {
        return $query->whereHasPermission($permission, $team, 'or');
    }

    /**
     * Get the users without any role.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function whereDoesntHaveRoles(Builder $query): Builder
    {
        return $query->doesntHave('roles');
    }

    /**
     * Get the users without any permission: neither given directly nor through their roles.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function whereDoesntHavePermissions(Builder $query): Builder
    {
        return $query->where(
            fn (Builder $query) => $query
                ->doesntHave('permissions')
                ->doesntHave('roles.permissions')
        );
    }
}
