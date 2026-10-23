<?php

declare(strict_types=1);

namespace Laratrust\Support;

use BackedEnum;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Contracts\Role;
use Ramsey\Uuid\UuidInterface;
use UnexpectedValueException;

/**
 * Loads and memoizes the roles and permissions of users and roles.
 *
 * Bound as a scoped instance, so Laravel discards it between HTTP requests,
 * queue jobs and Octane requests. Lookups go memory -> cache (when enabled) -> database.
 *
 * @internal
 */
final class PermissionRegistry
{
    /** @var array<string, PermissionLookup> */
    private array $lookups = [];

    /** @var array<string, StoredAssignments> */
    private array $stored = [];

    /** @var array<array-key, array<array-key, true>> Permission sets, keyed by role cache key. */
    private array $rolePermissions = [];

    /** @var array<string, array<string, int|string>> */
    private array $ids = [];

    public function __construct(private readonly Cache $cache) {}

    public function lookupFor(Model&LaratrustUser $user): PermissionLookup
    {
        $key = $user->laratrustCacheKey();

        return $this->lookups[$key] ??= $this->buildLookup($this->storedAssignments($user, $key));
    }

    /**
     * Get the role's permission names, as a set.
     *
     * @return array<array-key, true>
     */
    public function permissionsForRole(Model&Role $role): array
    {
        $this->loadRolePermissions([$role]);

        return $this->rolePermissions[$role->laratrustCacheKey()];
    }

    public function forgetUser(Model&LaratrustUser $user): void
    {
        $key = $user->laratrustCacheKey();

        unset($this->stored[$key], $this->lookups[$key]);
        $this->cache->forget($key);
    }

    public function forgetRole(Model&Role $role): void
    {
        $key = $role->laratrustCacheKey();

        unset($this->rolePermissions[$key], $this->ids['role']);
        // Any user's lookup may contain this role's permissions.
        $this->lookups = [];
        $this->cache->forget($key);
    }

    /**
     * Forget the memoized name -> key lookups for the given type.
     */
    public function forgetIds(string $type): void
    {
        unset($this->ids[$type]);
    }

    /**
     * Turn the role or permission names given to a check into a list.
     *
     * A string is split by "|" ("admin|editor"), enums are turned into their values.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $names
     * @return list<string>
     */
    public function names(string|array|BackedEnum $names): array
    {
        if (is_string($names)) {
            return explode('|', $names);
        }

        return array_map(
            fn (string|BackedEnum $name): string => (string) ($name instanceof BackedEnum ? $name->value : $name),
            array_values(is_array($names) ? $names : [$names]),
        );
    }

    /**
     * Get the key for a role, permission or team given as a model, enum, uuid, array, key or name.
     */
    public function idFor(mixed $object, string $type): int|string|null
    {
        if ($object === null || ($type === 'team' && ! Config::get('laratrust.teams.enabled'))) {
            return null;
        }

        if ($object instanceof BackedEnum) {
            $object = $object->value;
        }

        return match (true) {
            $object instanceof UuidInterface => $object->toString(),
            $object instanceof Model => $object->getKey(),
            is_array($object) => $object['id'],
            is_int($object), is_numeric($object) => $object,
            is_string($object) => $this->ids[$type][$object] ??= $this->findIdByName($object, $type),
            default => throw new InvalidArgumentException(
                'The given value must be a Model, BackedEnum, UuidInterface, array{id: int|string}, int or string.'
            ),
        };
    }

    private function storedAssignments(Model&LaratrustUser $user, string $key): StoredAssignments
    {
        if (isset($this->stored[$key])) {
            return $this->stored[$key];
        }

        if (! $this->cacheEnabled()) {
            return $this->stored[$key] = $this->loadAssignments($user);
        }

        // An invalid or outdated cached value is treated as a cache miss.
        if (($assignments = StoredAssignments::fromArray($this->cache->get($key))) instanceof StoredAssignments) {
            return $this->stored[$key] = $assignments;
        }

        $assignments = $this->loadAssignments($user);
        $this->cache->put($key, $assignments->toArray(), $this->ttl());

        return $this->stored[$key] = $assignments;
    }

    private function buildLookup(StoredAssignments $assignments): PermissionLookup
    {
        /** @var class-string<Model&Role> $roleClass */
        $roleClass = Config::get('laratrust.models.role');
        $prototype = new $roleClass;

        // A role can be assigned more than once (in different teams), but its permissions are loaded once.
        $uniqueRoles = collect($assignments->roles)
            ->unique('id')
            ->map(fn (array $role) => $prototype->newFromBuilder([
                $prototype->getKeyName() => $role['id'],
                'name' => $role['name'],
            ]))
            ->values()
            ->all();

        $this->loadRolePermissions($uniqueRoles);

        $roleCacheKeys = collect($uniqueRoles)
            ->mapWithKeys(fn (Model&Role $role): array => [(string) $role->getKey() => $role->laratrustCacheKey()])
            ->all();

        // The lookup references the shared permission sets, it doesn't copy them.
        return PermissionLookup::build(
            $assignments,
            $roleCacheKeys,
            array_intersect_key($this->rolePermissions, array_flip($roleCacheKeys)),
        );
    }

    /**
     * Make sure the roles' permissions are in memory: from the cache, otherwise from the database.
     *
     * @param  array<int, Model&Role>  $roles
     */
    private function loadRolePermissions(array $roles): void
    {
        $missing = $this->rolesNotInMemory($roles);

        if ($missing !== [] && $this->cacheEnabled()) {
            $missing = $this->recoverFromCache($missing);
        }

        if ($missing !== []) {
            $this->recoverFromDatabase($missing);
        }
    }

    /**
     * @param  array<int, Model&Role>  $roles
     * @return array<string, Model&Role> The roles not loaded yet in this request, keyed by cache key.
     */
    private function rolesNotInMemory(array $roles): array
    {
        return collect($roles)
            ->keyBy(fn (Model&Role $role): string => $role->laratrustCacheKey())
            ->reject(fn (Model&Role $role, string $key): bool => isset($this->rolePermissions[$key]))
            ->all();
    }

    /**
     * Remember the roles' permissions found in the cache.
     *
     * @param  array<string, Model&Role>  $rolesByCacheKey
     * @return array<string, Model&Role> The roles that weren't in the cache, keyed by cache key.
     */
    private function recoverFromCache(array $rolesByCacheKey): array
    {
        $cached = collect($this->cache->many(array_keys($rolesByCacheKey)))
            ->filter(
                fn (mixed $names): bool => is_array($names)
                && array_is_list($names)
                && Arr::every($names, fn ($name): bool => is_string($name))
            );

        $this->rolePermissions = $cached->map(fn (array $names): array => array_fill_keys($names, true))->all()
            + $this->rolePermissions;

        return collect($rolesByCacheKey)->diffKeys($cached)->all();
    }

    /**
     * Load the roles' permissions from the database with eager loading,
     * remember them for this request and cache them (when enabled).
     *
     * @param  non-empty-array<string, Model&Role>  $rolesByCacheKey
     */
    private function recoverFromDatabase(array $rolesByCacheKey): void
    {
        $roles = collect($rolesByCacheKey);
        $cacheKeys = $roles->mapWithKeys(fn (Model&Role $role, string $cacheKey): array => [$role->getKey() => $cacheKey]);

        $prototype = reset($rolesByCacheKey);

        // Only the keys and names are needed; the pivot keys used to match them are added by Eloquent.
        $loaded = $prototype->newQuery()
            ->whereKey($roles->map(fn (Model&Role $role) => $role->getKey())->values())
            ->with(['permissions' => fn (BelongsToMany $permissions) => $permissions->select([
                $permissions->getRelated()->getQualifiedKeyName(),
                $permissions->getRelated()->qualifyColumn('name'),
            ])])
            ->get([$prototype->getQualifiedKeyName()])
            ->mapWithKeys(fn (Model $role): array => [
                $cacheKeys[$role->getKey()] => $role->getRelation('permissions')->pluck('name')->all(),
            ]);

        // Roles the query doesn't return (deleted, or hidden by a global scope like SoftDeletes)
        // are remembered as having no permissions, so they aren't queried again.
        $loaded = $loaded->union($roles->map(fn (): array => []))->all();

        $this->rolePermissions = array_map(fn (array $names): array => array_fill_keys($names, true), $loaded)
            + $this->rolePermissions;

        // The cache stores plain lists of names.
        if ($this->cacheEnabled()) {
            $this->cache->putMany($loaded, $this->ttl());
        }
    }

    private function loadAssignments(Model&LaratrustUser $user): StoredAssignments
    {
        $teamKey = Config::get('laratrust.foreign_keys.team');
        $roles = $user->roles();
        $permissions = $user->permissions();
        $teamOf = fn (Model $model): int|string|null => $model->getRelation($roles->getPivotAccessor())->getAttribute($teamKey);

        return new StoredAssignments(
            roles: $roles
                ->get([$roles->getRelated()->getQualifiedKeyName(), $roles->getRelated()->qualifyColumn('name')])
                ->map(fn (Model $role): array => [
                    'id' => $this->keyOf($role),
                    'name' => (string) $role->getAttribute('name'),
                    'team' => $teamOf($role),
                ])
                ->all(),
            permissions: $permissions
                ->get([$permissions->getRelated()->qualifyColumn('name')])
                ->map(fn (Model $permission): array => [
                    'name' => (string) $permission->getAttribute('name'),
                    'team' => $teamOf($permission),
                ])
                ->all(),
        );
    }

    private function keyOf(Model $model): int|string
    {
        $key = $model->getKey();

        if (! is_int($key) && ! is_string($key)) {
            throw new UnexpectedValueException('Laratrust models must have an int or string primary key.');
        }

        return $key;
    }

    private function findIdByName(string $name, string $type): int|string
    {
        /** @var class-string<Model> $class */
        $class = Config::get("laratrust.models.{$type}");

        return $this->keyOf($class::query()->where('name', $name)->firstOrFail());
    }

    private function cacheEnabled(): bool
    {
        return (bool) Config::get('laratrust.cache.enabled');
    }

    private function ttl(): int
    {
        return (int) Config::get('laratrust.cache.expiration_time', 3600);
    }
}
