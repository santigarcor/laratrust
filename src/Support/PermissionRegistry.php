<?php

declare(strict_types=1);

namespace Laratrust\Support;

use BackedEnum;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
 *
 * @phpstan-import-type RoleData from PermissionLookup
 */
final class PermissionRegistry
{
    /** @var array<string, PermissionLookup> */
    private array $lookups = [];

    /** @var array<string, StoredAssignments> */
    private array $stored = [];

    /** @var array<array-key, RoleData> The roles' names and permission sets, keyed by role cache key. */
    private array $roles = [];

    /** @var array<string, array<string, int|string|false>> Keys by name, false for the names no model has. */
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
        $this->loadRoles([$role]);

        return $this->roles[$role->laratrustCacheKey()]['permissions'];
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

        unset($this->roles[$key], $this->ids['role']);
        // Any user's lookup may reference this role.
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
     * Forget everything memoized, so the next checks read the cache or the database again.
     */
    public function reset(): void
    {
        $this->lookups = [];
        $this->stored = [];
        $this->roles = [];
        $this->ids = [];
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
     *
     * @throws ModelNotFoundException When no model has the given name.
     */
    public function idFor(mixed $object, string $type): int|string|null
    {
        $id = $this->findIdFor($object, $type);

        if ($id === false) {
            throw (new ModelNotFoundException)->setModel($this->modelClass($type));
        }

        return $id;
    }

    /**
     * Like idFor(), but a name that no model has gives false instead of throwing, so a check can match nothing.
     */
    public function findIdFor(mixed $object, string $type): int|string|false|null
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
            is_array($object) => $this->idFromArray($object, $type),
            is_int($object), is_numeric($object) => $object,
            is_string($object) => $this->idForName($object, $type),
            default => throw new InvalidArgumentException(
                "The {$type} must be a Model, BackedEnum, UuidInterface, array{id: int|string}, int or string."
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
        $prototype = new (ModelClasses::role());

        // A role can be assigned more than once (in different teams), but it's loaded once.
        $uniqueRoles = collect($assignments->roles)
            ->unique('id')
            ->map(fn (array $role) => $prototype->newFromBuilder([$prototype->getKeyName() => $role['id']]))
            ->values()
            ->all();

        $this->loadRoles($uniqueRoles);

        $roleCacheKeys = collect($uniqueRoles)
            ->mapWithKeys(fn (Model&Role $role): array => [(string) $role->getKey() => $role->laratrustCacheKey()])
            ->all();

        return PermissionLookup::build(
            $assignments,
            $roleCacheKeys,
            array_intersect_key($this->roles, array_flip($roleCacheKeys)),
        );
    }

    /**
     * Make sure the roles' names and permissions are in memory: from the cache, otherwise from the database.
     *
     * @param  array<int, Model&Role>  $roles
     */
    private function loadRoles(array $roles): void
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
            ->reject(fn (Model&Role $role, string $key): bool => isset($this->roles[$key]))
            ->all();
    }

    /**
     * Remember the roles found in the cache.
     *
     * @param  array<string, Model&Role>  $rolesByCacheKey
     * @return array<string, Model&Role> The roles that weren't in the cache, keyed by cache key.
     */
    private function recoverFromCache(array $rolesByCacheKey): array
    {
        $cached = [];

        foreach ($this->cache->many(array_keys($rolesByCacheKey)) as $cacheKey => $data) {
            if (($role = $this->roleFromCache($data)) !== null) {
                $cached[$cacheKey] = $role;
            }
        }

        $this->roles = $cached + $this->roles;

        return array_diff_key($rolesByCacheKey, $cached);
    }

    /**
     * Load the roles' names and permissions from the database with eager loading,
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
        $found = $prototype->newQuery()
            ->whereKey($roles->map(fn (Model&Role $role) => $role->getKey())->values())
            ->with(['permissions' => fn (BelongsToMany $permissions) => $permissions->select([
                $permissions->getRelated()->getQualifiedKeyName(),
                $permissions->getRelated()->qualifyColumn('name'),
            ])])
            ->get([$prototype->getQualifiedKeyName(), $prototype->qualifyColumn('name')])
            ->keyBy(fn (Model $role): string => $cacheKeys[$role->getKey()]);

        // Roles the query doesn't return (deleted, or hidden by a global scope like SoftDeletes)
        // are remembered as missing, so they aren't queried again and count as not assigned.
        $loaded = $roles
            ->map(function (Model&Role $role, string $cacheKey) use ($found): array {
                $model = $found->get($cacheKey);

                return $model === null ? ['name' => null, 'permissions' => []] : [
                    'name' => (string) $model->getAttribute('name'),
                    'permissions' => $model->getRelation('permissions')->pluck('name')->all(),
                ];
            })
            ->all();

        $this->roles = array_map(
            fn (array $role): array => ['name' => $role['name'], 'permissions' => array_fill_keys($role['permissions'], true)],
            $loaded,
        ) + $this->roles;

        // The cache stores the names as plain lists.
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
            // Without the role's global scopes (like SoftDeletes): a hidden role is kept by key and
            // loaded as missing, so restoring it gives it back to its users without flushing them.
            roles: $roles
                ->withoutGlobalScopes()
                ->get([$roles->getRelated()->getQualifiedKeyName()])
                ->map(fn (Model $role): array => [
                    'id' => $this->keyOf($role),
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

    /**
     * Restore a role from the cache, or null if the cached value isn't valid.
     *
     * @return RoleData|null
     */
    private function roleFromCache(mixed $data): ?array
    {
        if (! is_array($data) || ! array_key_exists('name', $data) || ! is_array($permissions = $data['permissions'] ?? null)) {
            return null;
        }

        $name = $data['name'];

        if (($name !== null && ! is_string($name)) || ! array_is_list($permissions) || ! Arr::every($permissions, fn (mixed $permission): bool => is_string($permission))) {
            return null;
        }

        return ['name' => $name, 'permissions' => array_fill_keys($permissions, true)];
    }

    private function keyOf(Model $model): int|string
    {
        $key = $model->getKey();

        if (! is_int($key) && ! is_string($key)) {
            throw new UnexpectedValueException('Laratrust models must have an int or string primary key.');
        }

        return $key;
    }

    /**
     * @param  array<array-key, mixed>  $object
     */
    private function idFromArray(array $object, string $type): int|string
    {
        $id = $object['id'] ?? null;

        if (! is_int($id) && ! is_string($id)) {
            throw new InvalidArgumentException("The {$type} given as an array must have an int or string \"id\".");
        }

        return $id;
    }

    /**
     * The key of the model with the given name, or false if there's none. Both are remembered for the request.
     */
    private function idForName(string $name, string $type): int|string|false
    {
        if (! array_key_exists($name, $this->ids[$type] ?? [])) {
            $model = $this->modelClass($type)::query()->where('name', $name)->first();
            $this->ids[$type][$name] = $model === null ? false : $this->keyOf($model);
        }

        return $this->ids[$type][$name];
    }

    /**
     * @return class-string<Model>
     */
    private function modelClass(string $type): string
    {
        return match ($type) {
            'role' => ModelClasses::role(),
            'permission' => ModelClasses::permission(),
            'team' => ModelClasses::team(),
            default => throw new InvalidArgumentException("Unknown Laratrust model type [{$type}]."),
        };
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
