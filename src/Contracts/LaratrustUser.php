<?php

declare(strict_types=1);

namespace Laratrust\Contracts;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Ramsey\Uuid\UuidInterface;

/**
 * @phpstan-type Assignable array{id: int|string}|string|int|Model|UuidInterface|BackedEnum
 */
interface LaratrustUser
{
    /**
     * Many-to-Many relations with Role.
     *
     * @return MorphToMany<covariant Model, covariant Model>
     */
    public function roles(): MorphToMany;

    /**
     * Many-to-Many relations with Permission.
     *
     * @return MorphToMany<covariant Model, covariant Model>
     */
    public function permissions(): MorphToMany;

    /**
     * Checks if the user has a role by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $name
     */
    public function hasRole(
        string|array|BackedEnum $name,
        mixed $team = null,
        bool $requireAll = false
    ): bool;

    /**
     * Check if user has a permission by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function hasPermission(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool;

    /**
     * Check if user has a permission by its name.
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function isAbleTo(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool;

    /**
     * Checks role(s) and permission(s).
     *
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $roles
     * @param  string|array<string|BackedEnum>|BackedEnum  $permissions
     * @param  array{validate_all?: bool, return_type?: 'boolean'|'array'|'both'}  $options
     * @return bool|array{roles: array<string, bool>, permissions: array<string, bool>}|array{bool, array{roles: array<string, bool>, permissions: array<string, bool>}}
     *
     * @throws \InvalidArgumentException
     */
    public function ability(
        string|array|BackedEnum $roles,
        string|array|BackedEnum $permissions,
        mixed $team = null,
        array $options = []
    ): array|bool;

    /**
     * Add a role to the user.
     *
     * @param  Assignable  $role
     */
    public function addRole(
        array|string|int|Model|UuidInterface|BackedEnum $role,
        mixed $team = null
    ): static;

    /**
     * Remove a role from the user.
     *
     * @param  Assignable  $role
     */
    public function removeRole(
        array|string|int|Model|UuidInterface|BackedEnum $role,
        mixed $team = null
    ): static;

    /**
     * Add multiple roles to a user.
     *
     * @param  array<array-key, Assignable>  $roles
     */
    public function addRoles(
        array $roles = [],
        mixed $team = null
    ): static;

    /**
     * Remove multiple roles from a user. An empty list removes nothing.
     *
     * @param  array<array-key, Assignable>  $roles
     */
    public function removeRoles(
        array $roles,
        mixed $team = null
    ): static;

    /**
     * Sync roles to the user.
     *
     * @param  array<array-key, Assignable>  $roles
     */
    public function syncRoles(
        array $roles = [],
        mixed $team = null,
        bool $detaching = true
    ): static;

    /**
     * Add direct permissions to the user.
     *
     * @param  Assignable  $permission
     */
    public function givePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission,
        mixed $team = null
    ): static;

    /**
     * Remove direct permissions from the user.
     *
     * @param  Assignable  $permission
     */
    public function removePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission,
        mixed $team = null
    ): static;

    /**
     * Add multiple permissions to the user.
     *
     * @param  array<array-key, Assignable>  $permissions
     */
    public function givePermissions(
        array $permissions = [],
        mixed $team = null
    ): static;

    /**
     * Remove multiple permissions from the user. An empty list removes nothing.
     *
     * @param  array<array-key, Assignable>  $permissions
     */
    public function removePermissions(
        array $permissions,
        mixed $team = null
    ): static;

    /**
     * Sync permissions to the user.
     *
     * @param  array<array-key, Assignable>  $permissions
     */
    public function syncPermissions(
        array $permissions = [],
        mixed $team = null,
        bool $detaching = true
    ): static;

    /**
     * Return all the user permissions.
     *
     *
     * @param  array<int, string>|null  $columns
     * @return Collection<int, Model>
     */
    public function allPermissions(?array $columns = null, mixed $team = false): Collection;

    /**
     * Flush the user's cached roles and permissions.
     */
    public function flushLaratrustCache(): void;

    /**
     * Get the key used to cache the user's roles and permissions.
     */
    public function laratrustCacheKey(): string;
}
