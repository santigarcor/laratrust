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
use Ramsey\Uuid\UuidInterface;

trait HasGroupsAndRolesAndPermissions
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
            if (method_exists($user, 'bootSoftDeletes') && !$user->forceDeleting) {
                return;
            }

            $user->groups()->sync([]);
            $user->roles()->sync([]);
            $user->permissions()->sync([]);
        });
    }

    /**
     * Many-to-Many relations with Group.
     */
    public function groups(): MorphToMany
    {
        $roles = $this->morphToMany(
            Config::get('laratrust.models.group'),
            'user',
            Config::get('laratrust.tables.group_user'),
            Config::get('laratrust.foreign_keys.user'),
            Config::get('laratrust.foreign_keys.group')
        );

        return $roles;
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

        return $roles;
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
    public function getRoles(): array
    {
        return $this->laratrustUserChecker()->getCurrentUserRoles();
    }

    /**
     * Get the the names of the user's groups.
     */
    public function getGroups(): array
    {
        return $this->laratrustUserChecker()->getCurrentUserGroups();
    }

    /**
     * Get the the names of the user's permissions.
     */
    public function getPermissions(): array
    {
        return $this->laratrustUserChecker()->getCurrentUserPermissions();
    }

    /**
     * Checks if the user has a role by its name.
     */
    public function hasRole(
        string|array|BackedEnum $name,
        bool $requireAll = false
    ): bool {
        return $this->laratrustUserChecker()->currentUserHasRole(
            $name,
            $requireAll
        );
    }

    /**
     * Checks if the user is in a group by its name.
     */
    public function isInGroup(
        string|array|BackedEnum $name,
        bool $requireAll = false
    ): bool {
        return $this->laratrustUserChecker()->currentUserHasGroup(
            $name,
            $requireAll
        );
    }

    /**
     * Check if user has a permission by its name.
     */
    public function hasPermission(
        string|array|BackedEnum $permission,
        bool $requireAll = false
    ): bool {
        return $this->laratrustUserChecker()->currentUserHasPermission(
            $permission,
            $requireAll
        );
    }

    /**
     * Check if user has a permission by its name.
     */
    public function isAbleTo(
        string|array|BackedEnum $permission,
        bool $requireAll = false
    ): bool {
        return $this->hasPermission($permission, $requireAll);
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
        array $options = []
    ): array|bool {
        return $this->laratrustUserChecker()->currentUserHasAbility(
            $roles,
            $permissions,
            $options
        );
    }

    /**
     * Check if the given relationship is a valid laratrust relationship.
     */
    private function isValidRelationship(string $relationship): bool
    {
        return in_array($relationship, ['roles', 'permissions', 'groups']);
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     */
    private function attachModel(
        string $relationship,
        array|string|int|Model|UuidInterface|BackedEnum $object
    ): static {
        if (!$this->isValidRelationship($relationship)) {
            throw new InvalidArgumentException;
        }

        $attributes = [];
        $objectType = Str::singular($relationship);
        $object = Helper::getIdFor($object, $objectType);

        $this->$relationship()->attach(
            $object,
            $attributes
        );
        $this->flushCache();
        $this->fireLaratrustEvent("{$objectType}.added", [$this, $object]);

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     */
    private function detachModel(
        string $relationship,
        array|string|int|Model|UuidInterface|BackedEnum $object
    ): static {
        if (!$this->isValidRelationship($relationship)) {
            throw new InvalidArgumentException;
        }

        $objectType = Str::singular($relationship);
        $relationshipQuery = $this->$relationship();

        $object = Helper::getIdFor($object, $objectType);
        $relationshipQuery->detach($object);

        $this->flushCache();
        $this->fireLaratrustEvent("{$objectType}.removed", [$this, $object]);

        return $this;
    }

    /**
     * Alias to eloquent many-to-many relation's sync() method.
     */
    private function syncModels(
        string $relationship,
        array|string|int|Model|UuidInterface|BackedEnum $objects,
        bool $detaching
    ): static {
        if (!$this->isValidRelationship($relationship)) {
            throw new InvalidArgumentException;
        }

        $objectType = Str::singular($relationship);
        $mappedObjects = [];

        foreach ($objects as $object) {
            $mappedObjects[] = Helper::getIdFor($object, $objectType);
        }

        $relationshipToSync = $this->$relationship();

        $result = $relationshipToSync->sync($mappedObjects, $detaching);

        $this->flushCache();
        $this->fireLaratrustEvent("{$objectType}.synced", [$this, $result]);

        return $this;
    }

    /**
     * Add a role to the user.
     */
    public function addRole(
        array|string|int|Model|UuidInterface|BackedEnum $role
    ): static {
        return $this->attachModel('roles', $role);
    }

    /**
     * Add the user to a group.
     */
    public function addToGroup(
        array|string|int|Model|UuidInterface|BackedEnum $group
    ): static {
        return $this->attachModel('groups', $group);
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(
        array|string|int|Model|UuidInterface|BackedEnum $role
    ): static {
        return $this->detachModel('roles', $role);
    }

    /**
     * Remove the user from a group.
     */
    public function removeFromGroup(
        array|string|int|Model|UuidInterface|BackedEnum $group
    ): static {
        return $this->detachModel('groups', $group);
    }

    /**
     * Add multiple roles to a user.
     */
    public function addRoles(
        array $roles = []
    ): static {
        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * Add user to multiple groups.
     */
    public function addToGroups(
        array $groups = []
    ): static {
        foreach ($groups as $group) {
            $this->addToGroup($group);
        }

        return $this;
    }

    /**
     * Remove multiple roles from a user.
     */
    public function removeRoles(
        array $roles = []
    ): static {
        if (empty($roles)) {
            return $this->syncRoles([]);
        }

        foreach ($roles as $role) {
            $this->removeRole($role);
        }

        return $this;
    }

    /**
     * Remove user from multiple groups.
     */
    public function removeFromGroups(
        array $groups = []
    ): static {
        if (empty($groups)) {
            return $this->syncGroups([]);
        }

        foreach ($groups as $group) {
            $this->removeFromGroup($group);
        }

        return $this;
    }

    /**
     * Sync roles to the user.
     */
    public function syncRoles(
        array $roles = [],
        bool $detaching = true
    ): static {
        return $this->syncModels('roles', $roles, $detaching);
    }

    /**
     * Sync groups to the user.
     */
    public function syncGroups(
        array $groups = [],
        bool $detaching = true
    ): static {
        return $this->syncModels('groups', $groups, $detaching);
    }

    /**
     * Sync roles to the user without detaching.
     */
    public function syncRolesWithoutDetaching(
        array $roles = [],
    ): static {
        return $this->syncRoles($roles, false);
    }

    /**
     * Add direct permissions to the user.
     */
    public function givePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission,
    ): static {
        return $this->attachModel('permissions', $permission);
    }

    /**
     * Remove direct permissions from the user.
     */
    public function removePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission,
    ): static {
        return $this->detachModel('permissions', $permission);
    }

    /**
     * Add multiple permissions to the user.
     */
    public function givePermissions(
        array $permissions = [],
    ): static {
        foreach ($permissions as $permission) {
            $this->givePermission($permission);
        }

        return $this;
    }

    /**
     * Remove multiple permissions from the user.
     */
    public function removePermissions(
        array $permissions = [],
    ): static {
        if (!$permissions) {
            return $this->syncPermissions([]);
        }

        foreach ($permissions as $permission) {
            $this->removePermission($permission);
        }

        return $this;
    }

    /**
     * Sync permissions to the user.
     */
    public function syncPermissions(
        array $permissions = [],
        bool $detaching = true
    ): static {
        return $this->syncModels('permissions', $permissions, $detaching);
    }

    /**
     * Sync permissions to the user without detaching.
     */
    public function syncPermissionsWithoutDetaching(
        array $permissions = []
    ): static {
        return $this->syncPermissions($permissions, false);
    }

    /**
     * Return all the user permissions.
     *
     * @return Collection<\Laratrust\Contracts\Permission>
     */
    public function allPermissions(array $columns = null): Collection
    {
        $columns = is_array($columns) ? $columns : null;
        if ($columns) {
            $columns[] = 'id';
            $columns = array_unique($columns);
        }
        $withColumns = $columns ? ':' . implode(',', $columns) : '';

        $groups = $this->groups()
            ->with([
                'roles' => function ($query) use ($withColumns) {
                    $query->with("permissions{$withColumns}");
                },
            ])
            ->get();

        $groupsPermissions = $groups->flatMap(function ($group) {
            return $group->roles->flatMap(function ($role) {
                return $role->permissions;
            });
        });

        $roles = $this->roles()
            ->with("permissions{$withColumns}")
            ->get();

        $rolesPermissions = $roles->flatMap(function ($role) {
            return $role->permissions;
        });

        $directPermissions = $this->permissions();

        return $directPermissions
            ->get($columns ?? ['*'])
            ->merge($groupsPermissions)
            ->merge($rolesPermissions)
            ->unique('id');
    }

    /**
     * Flush the user's cache.
     */
    public function flushCache(bool $recreate = true): void
    {
        $this->laratrustUserChecker()->currentUserFlushCache($recreate);
    }

    /**
     * Resolve and cache user's permissions.
     */
    public function resolvePermissions(): array
    {
        return $this->laratrustUserChecker()->resolvePermissions();
    }
}
