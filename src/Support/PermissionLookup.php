<?php

declare(strict_types=1);

namespace Laratrust\Support;

use Illuminate\Support\Str;

/**
 * A lookup table of a user's roles and permissions, so checks are hash lookups.
 *
 * Everything is grouped by team scope:
 * - null: ignore teams, so any assignment counts (teams disabled, or a non-strict check without a team).
 * - '' (NO_TEAM): only assignments without a team.
 * - a team key: only assignments in that team.
 *
 * A role's name and permissions aren't copied into each user: every scope only references the role
 * by its cache key, and the role's data is shared with the registry. A role whose name is null
 * doesn't exist anymore.
 *
 * @internal
 *
 * @phpstan-type RoleData array{name: string|null, permissions: array<array-key, true>}
 * @phpstan-type Scope array{
 *     roles: array<array-key, true>,
 *     roleKeys: array<array-key, true>,
 *     permissions: array<array-key, true>
 * }
 */
final readonly class PermissionLookup
{
    public const string NO_TEAM = '';

    /** The scope of a team that doesn't exist, so nothing is assigned in it; it can't collide with a team key. */
    public const string UNKNOWN_TEAM = "\0unknown";

    /** The scope used when teams are ignored; it can't collide with a team key. */
    private const string ANY_TEAM = "\0any";

    private const array EMPTY_SCOPE = ['roles' => [], 'roleKeys' => [], 'permissions' => []];

    /**
     * @param list<array{string, string}>              $assignedRoles   [name, team scope] pairs, in assignment order.
     * @param array<string, Scope>                     $scopes          Role names, role cache keys and direct permissions per team scope.
     * @param array<array-key, array<array-key, true>> $rolePermissions Shared permission sets, keyed by role cache key.
     */
    private function __construct(
        private array $assignedRoles,
        private array $scopes,
        private array $rolePermissions,
    ) {
    }

    /**
     * @param array<string, string>      $roleCacheKeys Role cache keys, keyed by role key.
     * @param array<array-key, RoleData> $roles         The roles' names and permissions, keyed by role cache key.
     */
    public static function build(StoredAssignments $assignments, array $roleCacheKeys, array $roles): self
    {
        $assignedRoles = [];
        $scopes = [];

        foreach ($assignments->roles as $role) {
            $roleKey = $roleCacheKeys[(string) $role['id']];
            $name = $roles[$roleKey]['name'] ?? null;

            // The role was deleted, or a global scope (like SoftDeletes) hides it.
            if ($name === null) {
                continue;
            }

            $teamScope = self::teamScopeFor($role['team']);
            $assignedRoles[] = [$name, $teamScope];

            foreach ([$teamScope, self::ANY_TEAM] as $scope) {
                $scopes[$scope]['roles'][$name] = true;
                $scopes[$scope]['roleKeys'][$roleKey] = true;
            }
        }

        foreach ($assignments->permissions as $permission) {
            foreach ([self::teamScopeFor($permission['team']), self::ANY_TEAM] as $scope) {
                $scopes[$scope]['permissions'][$permission['name']] = true;
            }
        }

        return new self(
            $assignedRoles,
            array_map(fn (array $scope): array => $scope + self::EMPTY_SCOPE, $scopes),
            // The permission sets are referenced, not copied.
            array_map(fn (array $role): array => $role['permissions'], $roles),
        );
    }

    public static function teamScopeFor(int|string|null $team): string
    {
        return $team === null ? self::NO_TEAM : (string) $team;
    }

    /**
     * Check if the granted permission names include the given one.
     *
     * An exact name is a hash lookup. Wildcards are only supported in the
     * requested name ("posts.*"), which is matched against the granted names.
     *
     * @param array<array-key, true> $granted
     */
    public static function grants(array $granted, string $permission): bool
    {
        if (!str_contains($permission, '*')) {
            return isset($granted[$permission]);
        }

        return collect(array_keys($granted))
            ->contains(fn (int|string $name): bool => Str::is($permission, (string) $name));
    }

    /**
     * @return list<string>
     */
    public function roleNames(?string $teamScope): array
    {
        $names = [];

        foreach ($this->assignedRoles as [$name, $roleTeamScope]) {
            if ($teamScope === null || $teamScope === $roleTeamScope) {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function hasRole(string $name, ?string $teamScope): bool
    {
        return isset($this->scope($teamScope)['roles'][$name]);
    }

    public function hasPermission(string $name, ?string $teamScope): bool
    {
        $scope = $this->scope($teamScope);

        return self::grants($scope['permissions'], $name)
            || collect(array_keys($scope['roleKeys']))->contains(
                fn (int|string $roleKey): bool => self::grants($this->rolePermissions[$roleKey] ?? [], $name)
            );
    }

    /**
     * @return Scope
     */
    private function scope(?string $teamScope): array
    {
        return $this->scopes[$teamScope ?? self::ANY_TEAM] ?? self::EMPTY_SCOPE;
    }
}
