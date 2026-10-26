<?php

declare(strict_types=1);

namespace Laratrust\Traits;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laratrust\Checkers\UserChecker;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Events\PermissionAdded;
use Laratrust\Events\PermissionRemoved;
use Laratrust\Events\PermissionSynced;
use Laratrust\Events\RoleAdded;
use Laratrust\Events\RoleRemoved;
use Laratrust\Events\RoleSynced;
use Laratrust\Models\Team;
use Laratrust\Support\ModelClasses;
use Laratrust\Support\PermissionRegistry;
use Ramsey\Uuid\UuidInterface;
use UnexpectedValueException;

/**
 * @phpstan-import-type Assignable from LaratrustUser
 */
trait HasRolesAndPermissions
{
    use HasLaratrustScopes;

    /**
     * Boots the user model and adds event listener to
     * remove the many-to-many records when trying to delete.
     * Will NOT delete any records if the user model uses soft deletes.
     */
    public static function bootHasRolesAndPermissions(): void
    {
        $flushLaratrustCache = function (self $user): void {
            $user->flushLaratrustCache();
        };

        // If the user uses SoftDeletes.
        if (method_exists(static::class, 'restored')) {
            static::restored($flushLaratrustCache);
        }

        static::deleted($flushLaratrustCache);
        static::saved($flushLaratrustCache);

        static::deleting(function (self $user): void {
            // Soft deleting keeps the assignments, so the user can be restored with them.
            if (method_exists($user, 'isForceDeleting') && ! $user->isForceDeleting()) {
                return;
            }

            $user->roles()->detach();
            $user->permissions()->detach();
        });
    }

    /**
     * Many-to-Many relations with Role.
     *
     * @return MorphToMany<covariant Model, $this>
     */
    public function roles(): MorphToMany
    {
        $roles = $this->morphToMany(
            ModelClasses::role(),
            'user',
            Config::get('laratrust.tables.role_user'),
            Config::get('laratrust.foreign_keys.user'),
            Config::get('laratrust.foreign_keys.role')
        );

        if (Config::get('laratrust.teams.enabled')) {
            $roles->withPivot(Config::get('laratrust.foreign_keys.team'));
        }

        return $roles;
    }

    /**
     * Many-to-Many relations with Team associated through the roles.
     *
     * @return MorphToMany<covariant Model, $this>|null
     */
    public function rolesTeams(): ?MorphToMany
    {
        if (! Config::get('laratrust.teams.enabled')) {
            return null;
        }

        return $this->morphToMany(
            ModelClasses::team(),
            'user',
            Config::get('laratrust.tables.role_user'),
            Config::get('laratrust.foreign_keys.user'),
            Config::get('laratrust.foreign_keys.team')
        )
            ->withPivot(Config::get('laratrust.foreign_keys.role'));
    }

    /**
     * Many-to-Many relations with Team associated through the permissions user is given.
     *
     * @return MorphToMany<covariant Model, $this>|null
     */
    public function permissionsTeams(): ?MorphToMany
    {
        if (! Config::get('laratrust.teams.enabled')) {
            return null;
        }

        return $this->morphToMany(
            ModelClasses::team(),
            'user',
            Config::get('laratrust.tables.permission_user'),
            Config::get('laratrust.foreign_keys.user'),
            Config::get('laratrust.foreign_keys.team')
        )
            ->withPivot(Config::get('laratrust.foreign_keys.permission'));
    }

    /**
     * Get the teams the user has a role or a permission in.
     *
     * @param  array<int, string>|null  $columns
     * @return Collection<int, Model>
     */
    public function allTeams(?array $columns = null): Collection
    {
        if (! Config::get('laratrust.teams.enabled')) {
            return Collection::make();
        }

        $team = new (ModelClasses::team());
        // Qualified, so they're never ambiguous with the pivot table's columns.
        $select = $columns === null ? ['*'] : Collection::make($columns)
            ->push($team->getKeyName())
            ->unique()
            ->map(fn (string $column): string => $team->qualifyColumn($column))
            ->all();

        return $this->rolesTeams()?->get($select)
            ->merge($this->permissionsTeams()?->get($select) ?? [])
            ->unique($team->getKeyName())
            ->values() ?? Collection::make();
    }

    /**
     * Many-to-Many relations with Permission.
     *
     * @return MorphToMany<covariant Model, $this>
     */
    public function permissions(): MorphToMany
    {
        $permissions = $this->morphToMany(
            ModelClasses::permission(),
            'user',
            Config::get('laratrust.tables.permission_user'),
            Config::get('laratrust.foreign_keys.user'),
            Config::get('laratrust.foreign_keys.permission')
        );

        if (Config::get('laratrust.teams.enabled')) {
            $permissions->withPivot(Config::get('laratrust.foreign_keys.team'));
        }

        return $permissions;
    }

    /**
     * Return the right checker for the user model.
     */
    protected function laratrustUserChecker(): UserChecker
    {
        return new UserChecker($this);
    }

    /**
     * Get the names of the user's roles.
     *
     * @return list<string>
     */
    public function getRoles(mixed $team = null): array
    {
        return $this->laratrustUserChecker()->roles($team);
    }

    /**
     * Checks if the user has a role by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $name
     */
    public function hasRole(
        string|array|BackedEnum $name,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return $this->laratrustUserChecker()->hasRole(
            $name,
            $team,
            $requireAll
        );
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function hasPermission(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return $this->laratrustUserChecker()->hasPermission(
            $permission,
            $team,
            $requireAll
        );
    }

    /**
     * Check if user does not have a permission by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function doesntHavePermission(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return ! $this->hasPermission($permission, $team, $requireAll);
    }

    /**
     * Check if user has a permission by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function isAbleTo(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return $this->hasPermission($permission, $team, $requireAll);
    }

    /**
     * Check if user does not have a permission by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function isNotAbleTo(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return ! $this->isAbleTo($permission, $team, $requireAll);
    }

    /**
     * Checks role(s) and permission(s).
     *
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $roles
     * @param  string|array<string|BackedEnum>|BackedEnum  $permissions
     * @param  array{validate_all?: bool, return_type?: 'boolean'|'array'|'both'}  $options
     * @return bool|array{roles: array<string, bool>, permissions: array<string, bool>}|array{bool, array{roles: array<string, bool>, permissions: array<string, bool>}}
     *
     * @throws InvalidArgumentException
     */
    public function ability(
        string|array|BackedEnum $roles,
        string|array|BackedEnum $permissions,
        mixed $team = null,
        array $options = []
    ): array|bool {
        return $this->laratrustUserChecker()->ability(
            $roles,
            $permissions,
            $team,
            $options
        );
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param  Assignable  $object
     * @param  Assignable|null  $team
     */
    private function attachLaratrustModel(
        string $relationship,
        array|string|int|Model|UuidInterface|BackedEnum $object,
        array|string|int|Model|UuidInterface|BackedEnum|null $team
    ): static {
        $registry = app(PermissionRegistry::class);
        $objectType = Str::singular($relationship);
        $object = $registry->idFor($object, $objectType);
        // Null when teams are disabled.
        $team = $registry->idFor($team, 'team');
        $relation = $this->$relationship();
        $attributes = [];

        if (Config::get('laratrust.teams.enabled')) {
            $relation->wherePivot(Team::modelForeignKey(), $team);
            $attributes[Team::modelForeignKey()] = $team;
        }

        // Adding an assignment the user already has (in that team) does nothing.
        if ($relation->newPivotStatementForId($object)->exists()) {
            return $this;
        }

        $relation->attach($object, $attributes);
        $this->flushLaratrustCache();
        event($objectType === 'role'
            ? new RoleAdded($this, $object, $team)
            : new PermissionAdded($this, $object, $team));

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param  Assignable  $object
     * @param  Assignable|null  $team
     */
    private function detachLaratrustModel(
        string $relationship,
        array|string|int|Model|UuidInterface|BackedEnum $object,
        array|string|int|Model|UuidInterface|BackedEnum|null $team
    ): static {
        $registry = app(PermissionRegistry::class);
        $objectType = Str::singular($relationship);
        $relationshipQuery = $this->$relationship();
        $team = $registry->idFor($team, 'team');

        if (Config::get('laratrust.teams.enabled')) {
            $relationshipQuery->wherePivot(Team::modelForeignKey(), $team);
        }

        $object = $registry->idFor($object, $objectType);
        $relationshipQuery->detach($object);

        $this->flushLaratrustCache();
        event($objectType === 'role'
            ? new RoleRemoved($this, $object, $team)
            : new PermissionRemoved($this, $object, $team));

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's sync() method.
     *
     * @param  array<array-key, Assignable>  $objects
     * @param  Assignable|null  $team
     */
    private function syncLaratrustModels(
        string $relationship,
        array $objects,
        array|string|int|Model|UuidInterface|BackedEnum|null $team,
        bool $detaching
    ): static {
        $registry = app(PermissionRegistry::class);
        $objectType = Str::singular($relationship);
        $mappedObjects = [];
        $useTeams = Config::get('laratrust.teams.enabled');
        $team = $useTeams ? $registry->idFor($team, 'team') : null;

        foreach ($objects as $object) {
            if ($useTeams && $team !== null) {
                $mappedObjects[$registry->idFor($object, $objectType)] = [Team::modelForeignKey() => $team];
            } else {
                $mappedObjects[] = $registry->idFor($object, $objectType);
            }
        }

        $relationshipToSync = $this->$relationship();

        if ($useTeams) {
            $relationshipToSync->wherePivot(Team::modelForeignKey(), $team);
        }

        $result = $relationshipToSync->sync($mappedObjects, $detaching);

        $this->flushLaratrustCache();
        event($objectType === 'role'
            ? new RoleSynced($this, $result, $team)
            : new PermissionSynced($this, $result, $team));

        return $this;
    }

    /**
     * Add a role to the user.
     *
     * @param  Assignable  $role
     */
    public function addRole(
        array|string|int|Model|UuidInterface|BackedEnum $role,
        mixed $team = null
    ): static {
        return $this->attachLaratrustModel('roles', $role, $team);
    }

    /**
     * Remove a role from the user.
     *
     * @param  Assignable  $role
     */
    public function removeRole(
        array|string|int|Model|UuidInterface|BackedEnum $role,
        mixed $team = null
    ): static {
        return $this->detachLaratrustModel('roles', $role, $team);
    }

    /**
     * Add multiple roles to a user.
     *
     * @param  array<array-key, Assignable>  $roles
     */
    public function addRoles(
        array $roles = [],
        mixed $team = null
    ): static {
        foreach ($roles as $role) {
            $this->addRole($role, $team);
        }

        return $this;
    }

    /**
     * Remove multiple roles from a user. An empty list removes nothing: to remove them all, use syncRoles([]).
     *
     * @param  array<array-key, Assignable>  $roles
     */
    public function removeRoles(
        array $roles,
        mixed $team = null
    ): static {
        foreach ($roles as $role) {
            $this->removeRole($role, $team);
        }

        return $this;
    }

    /**
     * Sync roles to the user.
     *
     * @param  array<array-key, Assignable>  $roles
     */
    public function syncRoles(
        array $roles = [],
        mixed $team = null,
        bool $detaching = true
    ): static {
        return $this->syncLaratrustModels('roles', $roles, $team, $detaching);
    }

    /**
     * Sync roles to the user without detaching.
     *
     * @param  array<array-key, Assignable>  $roles
     */
    public function syncRolesWithoutDetaching(
        array $roles = [],
        mixed $team = null,
    ): static {
        return $this->syncRoles($roles, $team, false);
    }

    /**
     * Add direct permissions to the user.
     *
     * @param  Assignable  $permission
     */
    public function givePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission,
        mixed $team = null
    ): static {
        return $this->attachLaratrustModel('permissions', $permission, $team);
    }

    /**
     * Remove direct permissions from the user.
     *
     * @param  Assignable  $permission
     */
    public function removePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission,
        mixed $team = null
    ): static {
        return $this->detachLaratrustModel('permissions', $permission, $team);
    }

    /**
     * Add multiple permissions to the user.
     *
     * @param  array<array-key, Assignable>  $permissions
     */
    public function givePermissions(
        array $permissions = [],
        mixed $team = null
    ): static {
        foreach ($permissions as $permission) {
            $this->givePermission($permission, $team);
        }

        return $this;
    }

    /**
     * Remove multiple permissions from the user. An empty list removes nothing: to remove them all, use syncPermissions([]).
     *
     * @param  array<array-key, Assignable>  $permissions
     */
    public function removePermissions(
        array $permissions,
        mixed $team = null
    ): static {
        foreach ($permissions as $permission) {
            $this->removePermission($permission, $team);
        }

        return $this;
    }

    /**
     * Sync permissions to the user.
     *
     * @param  array<array-key, Assignable>  $permissions
     */
    public function syncPermissions(
        array $permissions = [],
        mixed $team = null,
        bool $detaching = true
    ): static {
        return $this->syncLaratrustModels('permissions', $permissions, $team, $detaching);
    }

    /**
     * Sync permissions to the user without detaching.
     *
     * @param  array<array-key, Assignable>  $permissions
     */
    public function syncPermissionsWithoutDetaching(
        array $permissions = [],
        mixed $team = null
    ): static {
        return $this->syncPermissions($permissions, $team, false);
    }

    /**
     * Return all the user permissions.
     *
     *
     * @param  array<int, string>|null  $columns
     * @return Collection<int, Model>
     */
    public function allPermissions(?array $columns = null, mixed $team = false): Collection
    {
        $roles = $this->roles();
        $permissions = $this->permissions();
        $permission = $permissions->getRelated();

        // false means any team; otherwise only the assignments made in that team (null = without a team).
        if (Config::get('laratrust.teams.enabled') && $team !== false) {
            $teamId = app(PermissionRegistry::class)->idFor($team, 'team');
            $roles->where($roles->qualifyPivotColumn(Team::modelForeignKey()), $teamId);
            $permissions->where($permissions->qualifyPivotColumn(Team::modelForeignKey()), $teamId);
        }

        // Qualified, so they're never ambiguous with the pivot table's columns.
        $select = $columns === null ? null : collect($columns)
            ->push($permission->getKeyName())
            ->unique()
            ->map(fn (string $column) => $permission->qualifyColumn($column))
            ->all();

        $rolesPermissions = [];

        foreach ($roles->with(['permissions' => fn ($query) => $select ? $query->select($select) : $query])->get() as $role) {
            foreach ($role->getRelation('permissions') as $rolePermission) {
                $rolesPermissions[] = $rolePermission;
            }
        }

        return $permissions
            ->get($select ?? ['*'])
            ->merge($rolesPermissions)
            ->unique($permission->getKeyName());
    }

    /**
     * Flush the user's cache.
     */
    public function flushLaratrustCache(): void
    {
        $this->laratrustUserChecker()->flush();
    }

    /**
     * Get the key used to cache the user's roles and permissions.
     *
     * Override it to scope the cache, e.g. per tenant in multi-tenant applications.
     */
    public function laratrustCacheKey(): string
    {
        $userModels = Config::array('laratrust.user_models');

        // The exact class first: a configured model can extend another configured one.
        $key = array_search(static::class, $userModels, true);

        if ($key === false) {
            $key = Collection::make($userModels)->search(fn (mixed $model): bool => is_string($model) && $this instanceof $model);
        }

        if ($key === false) {
            throw new UnexpectedValueException('Class '.static::class.' is not defined in the laratrust.user_models');
        }

        return "laratrust_user_{$key}_{$this->getKey()}";
    }
}
