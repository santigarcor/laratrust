<?php

declare(strict_types=1);

namespace Laratrust\Support;

use Illuminate\Contracts\Support\Arrayable;

/**
 * The roles and direct permissions assigned to a user, as stored in the database.
 *
 * @internal
 *
 * @phpstan-type StoredRole array{id: int|string, name: string, team: int|string|null}
 * @phpstan-type StoredPermission array{name: string, team: int|string|null}
 *
 * @implements Arrayable<string, array<int, array<string, int|string|null>>>
 */
final readonly class StoredAssignments implements Arrayable
{
    /**
     * @param  array<int, StoredRole>  $roles
     * @param  array<int, StoredPermission>  $permissions
     */
    public function __construct(
        public array $roles = [],
        public array $permissions = [],
    ) {}

    /**
     * Restore the assignments from the cache, or null if the cached value isn't valid.
     */
    public static function fromArray(mixed $data): ?self
    {
        if (! is_array($data) || ! is_array($data['roles'] ?? null) || ! is_array($data['permissions'] ?? null)) {
            return null;
        }

        $roles = array_map(self::roleFromArray(...), array_values($data['roles']));
        $permissions = array_map(self::permissionFromArray(...), array_values($data['permissions']));

        if (in_array(null, $roles, true) || in_array(null, $permissions, true)) {
            return null;
        }

        return new self($roles, $permissions);
    }

    /**
     * @return array{roles: array<int, StoredRole>, permissions: array<int, StoredPermission>}
     */
    public function toArray(): array
    {
        return ['roles' => $this->roles, 'permissions' => $this->permissions];
    }

    /**
     * @return StoredRole|null
     */
    private static function roleFromArray(mixed $data): ?array
    {
        $id = is_array($data) ? ($data['id'] ?? null) : null;
        $name = is_array($data) ? ($data['name'] ?? null) : null;
        $team = is_array($data) ? ($data['team'] ?? null) : null;

        if ((! is_int($id) && ! is_string($id)) || ! is_string($name) || ! self::isTeam($team)) {
            return null;
        }

        return ['id' => $id, 'name' => $name, 'team' => $team];
    }

    /**
     * @return StoredPermission|null
     */
    private static function permissionFromArray(mixed $data): ?array
    {
        $name = is_array($data) ? ($data['name'] ?? null) : null;
        $team = is_array($data) ? ($data['team'] ?? null) : null;

        if (! is_string($name) || ! self::isTeam($team)) {
            return null;
        }

        return ['name' => $name, 'team' => $team];
    }

    /**
     * @phpstan-assert-if-true int|string|null $team
     */
    private static function isTeam(mixed $team): bool
    {
        return $team === null || is_int($team) || is_string($team);
    }
}
