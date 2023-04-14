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
use Laratrust\Checkers\CheckersManager;
use Laratrust\Checkers\User\UserChecker;
use Laratrust\Helper;
use Laratrust\Models\Team;
use Ramsey\Uuid\UuidInterface;

trait HasRolesAndPermissions
{
    use HasLaratrustEvents;
    use HasLaratrustScopes;

    /**
     * Boots the user model and adds event listener to
     * remove the many-to-many records when trying to delete.
     * Will NOT delete any records if the user model uses soft deletes.
     */
    public static function bootLaratrustUserTrait(): void
    {
        $flushCache = function ($user) {
            $user->flushCache();
        };

        // If the user doesn't use SoftDeletes.
        if (method_exists(static::class, 'restored')) {
            static::restored($flushCache);
        }

        static::deleted($flushCache);
        static::saved($flushCache);

        static::deleting(function ($user) {
            if (method_exists($user, 'bootSoftDeletes') && ! $user->forceDeleting) {
                return;
            }

            $user->roles()->sync([]);
            $user->permissions()->sync([]);
        });
    }

    /**
     * Many-to-Many relations with Role.
     */
    public function roles(): MorphToMany
    {
        $roles = $this->morphToMany(
            Config::get('laratrust.models.role'),
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
     */
    public function rolesTeams(): ?MorphToMany
    {
        if (! Config::get('laratrust.teams.enabled')) {
            return null;
        }

        return $this->morphToMany(
            Config::get('laratrust.models.team'),
            'user',
            Config::get('laratrust.tables.role_user'),
            Config::get('laratrust.foreign_keys.user'),
            Config::get('laratrust.foreign_keys.team')
        )
            ->withPivot(Config::get('laratrust.foreign_keys.role'));
    }

    /**
     * Many-to-Many relations with Team associated through the permissions user is given.
     */
    public function permissionsTeams(): ?MorphToMany
    {
        if (! Config::get('laratrust.teams.enabled')) {
            return null;
        }

        return $this->morphToMany(
            Config::get('laratrust.models.team'),
            'user',
            Config::get('laratrust.tables.permission_user'),
            Config::get('laratrust.foreign_keys.user'),
            Config::get('laratrust.foreign_keys.team')
        )
            ->withPivot(Config::get('laratrust.foreign_keys.permission'));
    }

    /**
     * Get a collection of all user teams.
     */
    public function allTeams(?array $columns = null): Collection
    {
        $columns = is_array($columns) ? $columns : ['*'];
        if ($columns) {
            $columns[] = 'id';
            $columns = array_unique($columns);
        }

        if (! Config::get('laratrust.teams.enabled')) {
            return collect([]);
        }
        $permissionTeams = $this->permissionsTeams()->get($columns);
        $roleTeams = $this->rolesTeams()->get($columns);

        return $roleTeams->merge($permissionTeams)->unique('id');
    }

    /**
     * Many-to-Many relations with Permission.
     */
    public function permissions(): MorphToMany
    {
        $permissions = $this->morphToMany(
            Config::get('laratrust.models.permission'),
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
        return (new CheckersManager($this))->getUserChecker();
    }

    /**
     * Get the the names of the user's roles.
     */
    public function getRoles(mixed $team = null): array
    {
        return $this->laratrustUserChecker()->getCurrentUserRoles($team);
    }

    /**
     * Checks if the user has a role by its name.
     */
    public function hasRole(
        string|array|BackedEnum $name,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return $this->laratrustUserChecker()->currentUserHasRole(
            $name,
            $team,
            $requireAll
        );
    }

    /**
     * Check if user has a permission by its name.
     */
    public function hasPermission(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return $this->laratrustUserChecker()->currentUserHasPermission(
            $permission,
            $team,
            $requireAll
        );
    }

    /**
     * Check if user has a permission by its name.
     */
    public function isAbleTo(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool {
        return $this->hasPermission($permission, $team, $requireAll);
    }

    /**
     * Checks role(s) and permission(s).
     *
     * @param  array  $options  validate_all{true|false} or return_type{boolean|array|both}
     *
     * @throws \InvalidArgumentException
     */
    public function ability(
        string|array|BackedEnum $roles,
        string|array|BackedEnum $permissions,
        mixed $team = null,
        array $options = []
    ): array|bool {
        return $this->laratrustUserChecker()->currentUserHasAbility(
            $roles,
            $permissions,
            $team,
            $options
        );
    }

    /**
     * Check if the given relationship is a valid laratrust relationship.
     */
    private function isValidRelationship(string $relationship): bool
    {
        return in_array($relationship, ['roles', 'permissions']);
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     */
    private function attachModel(
        string $relationship,
        array|string|int|Model|UuidInterface|BackedEnum $object,
        array|string|int|Model|UuidInterface|null $team
    ): static {
        if (! $this->isValidRelationship($relationship)) {
            throw new InvalidArgumentException;
        }

        $attributes = [];
        $objectType = Str::singular($relationship);
        $object = Helper::getIdFor($object, $objectType);

        if (Config::get('laratrust.teams.enabled')) {
            $team = Helper::getIdFor($team, 'team');

            if (
                $this->$relationship()
                ->wherePivot(Team::modelForeignKey(), $team)
                ->wherePivot(Config::get("laratrust.foreign_keys.{$objectType}"), $object)
                ->count()
            ) {
                return $this;
            }

            $attributes[Team::modelForeignKey()] = $team;
        }

        $this->$relationship()->attach(
            $object,
            $attributes
        );
        $this->flushCache();
        $this->fireLaratrustEvent("{$objectType}.added", [$this, $object, $team]);

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     */
    private function detachModel(
        string $relationship,
        array|string|int|Model|UuidInterface|BackedEnum $object,
        array|string|int|Model|UuidInterface|null $team
    ): static {
        if (! $this->isValidRelationship($relationship)) {
            throw new InvalidArgumentException;
        }

        $objectType = Str::singular($relationship);
        $relationshipQuery = $this->$relationship();

        if (Config::get('laratrust.teams.enabled')) {
            $relationshipQuery->wherePivot(
                Team::modelForeignKey(),
                Helper::getIdFor($team, 'team')
            );
        }

        $object = Helper::getIdFor($object, $objectType);
        $relationshipQuery->detach($object);

        $this->flushCache();
        $this->fireLaratrustEvent("{$objectType}.removed", [$this, $object, $team]);

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's sync() method.
     */
    private function syncModels(
        string $relationship,
        array|string|int|Model|UuidInterface|BackedEnum $objects,
        array|string|int|Model|UuidInterface|null $team,
        bool $detaching
    ): static {
        if (! $this->isValidRelationship($relationship)) {
            throw new InvalidArgumentException;
        }

        $objectType = Str::singular($relationship);
        $mappedObjects = [];
        $useTeams = Config::get('laratrust.teams.enabled');
        $team = $useTeams ? Helper::getIdFor($team, 'team') : null;

        foreach ($objects as $object) {
            if ($useTeams && $team) {
                $mappedObjects[Helper::getIdFor($object, $objectType)] = [Team::modelForeignKey() => $team];
            } else {
                $mappedObjects[] = Helper::getIdFor($object, $objectType);
            }
        }

        $relationshipToSync = $this->$relationship();

        if ($useTeams) {
            $relationshipToSync->wherePivot(Team::modelForeignKey(), $team);
        }

        $result = $relationshipToSync->sync($mappedObjects, $detaching);

        $this->flushCache();
        $this->fireLaratrustEvent("{$objectType}.synced", [$this, $result, $team]);

        return $this;
    }

    /**
     * Add a role to the user.
     */
    public function addRole(
        array|string|int|Model|UuidInterface|BackedEnum $role,
        mixed $team = null
    ): static {
        return $this->attachModel('roles', $role, $team);
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(
        array|string|int|Model|UuidInterface|BackedEnum $role,
        mixed $team = null
    ): static {
        return $this->detachModel('roles', $role, $team);
    }

    /**
     * Add multiple roles to a user.
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
     * Remove multiple roles from a user.
     */
    public function removeRoles(
        array $roles = [],
        mixed $team = null
    ): static {
        if (empty($roles)) {
            return $this->syncRoles([], $team);
        }

        foreach ($roles as $role) {
            $this->removeRole($role, $team);
        }

        return $this;
    }

    /**
     * Sync roles to the user.
     */
    public function syncRoles(
        array $roles = [],
        mixed $team = null,
        bool $detaching = true
    ): static {
        return $this->syncModels('roles', $roles, $team, $detaching);
    }

    /**
     * Sync roles to the user without detaching.
     */
    public function syncRolesWithoutDetaching(
        array $roles = [],
        mixed $team = null,
    ): static {
        return $this->syncRoles($roles, $team, false);
    }

    /**
     * Add direct permissions to the user.
     */
    public function givePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission,
        mixed $team = null
    ): static {
        return $this->attachModel('permissions', $permission, $team);
    }

    /**
     * Remove direct permissions from the user.
     */
    public function removePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission,
        mixed $team = null
    ): static {
        return $this->detachModel('permissions', $permission, $team);
    }

    /**
     * Add multiple permissions to the user.
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
     * Remove multiple permissions from the user.
     */
    public function removePermissions(
        array $permissions = [],
        mixed $team = null
    ): static {
        if (! $permissions) {
            return $this->syncPermissions([], $team);
        }

        foreach ($permissions as $permission) {
            $this->removePermission($permission, $team);
        }

        return $this;
    }

    /**
     * Sync permissions to the user.
     */
    public function syncPermissions(
        array $permissions = [],
        mixed $team = null,
        bool $detaching = true
    ): static {
        return $this->syncModels('permissions', $permissions, $team, $detaching);
    }

    /**
     * Sync permissions to the user without detaching.
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
     * @return Collection<\Laratrust\Contracts\Permission>
     */
    public function allPermissions(array $columns = null, $team = false): Collection
    {
        $columns = is_array($columns) ? $columns : null;
        if ($columns) {
            $columns[] = 'id';
            $columns = array_unique($columns);
        }
        $withColumns = $columns ? ':'.implode(',', $columns) : '';

        $roles = $this->roles()
            ->when(
                Config::get('laratrust.teams.enabled') && $team !== false,
                fn ($query) => $query->whereHas('permissions', function ($permissionQuery) use ($team) {
                    $permissionQuery->where(
                        Config::get('laratrust.foreign_keys.team'),
                        Helper::getIdFor($team, 'team')
                    );
                })
            )
            ->with("permissions{$withColumns}")
            ->get();

        $rolesPermissions = $roles->flatMap(function ($role) {
            return $role->permissions;
        });

        $directPermissions = $this->permissions()
            ->when(
                Config::get('laratrust.teams.enabled') && $team !== false,
                fn ($query) => $query->where(
                    config('laratrust.foreign_keys.team'),
                    Helper::getIdFor($team, 'team')
                )
            );

        return $directPermissions
            ->get($columns ?? ['*'])
            ->merge($rolesPermissions)
            ->unique('id');
    }

    /**
     * Flush the user's cache.
     */
    public function flushCache(): void
    {
        $this->laratrustUserChecker()->currentUserFlushCache();
    }
}
