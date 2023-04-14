<?php

declare(strict_types=1);

namespace Laratrust\Contracts;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Ramsey\Uuid\UuidInterface;

interface LaratrustUser
{
    /**
     * Many-to-Many relations with Role.
     */
    public function roles(): MorphToMany;

    /**
     * Many-to-Many relations with Permission.
     */
    public function permissions(): MorphToMany;

    /**
     * Checks if the user has a role by its name.
     */
    public function hasRole(
        string|array|BackedEnum $name,
        mixed $team = null,
        bool $requireAll = false
    ): bool;

    /**
     * Check if user has a permission by its name.
     */
    public function hasPermission(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool;

    /**
     * Check if user has a permission by its name.
     */
    public function isAbleTo(
        string|array|BackedEnum $permission,
        mixed $team = null,
        bool $requireAll = false
    ): bool;

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
    ): array|bool;

    /**
     * Add a role to the user.
     */
    public function addRole(
        array|string|int|Model|UuidInterface|BackedEnum $role,
        mixed $team = null
    ): static;

    /**
     * Remove a role from the user.
     */
    public function removeRole(
        array|string|int|Model|UuidInterface|BackedEnum $role,
        mixed $team = null
    ): static;

    /**
     * Add multiple roles to a user.
     */
    public function addRoles(
        array $roles = [],
        mixed $team = null
    ): static;

    /**
     * Remove multiple roles from a user.
     */
    public function removeRoles(
        array $roles = [],
        mixed $team = null
    ): static;

    /**
     * Sync roles to the user.
     */
    public function syncRoles(
        array $roles = [],
        mixed $team = null,
        bool $detaching = true
    ): static;

    /**
     * Add direct permissions to the user.
     */
    public function givePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission,
        mixed $team = null
    ): static;

    /**
     * Remove direct permissions from the user.
     */
    public function removePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission,
        mixed $team = null
    ): static;

    /**
     * Add multiple permissions to the user.
     */
    public function givePermissions(
        array $permissions = [],
        mixed $team = null
    ): static;

    /**
     * Remove multiple permissions from the user.
     */
    public function removePermissions(
        array $permissions = [],
        mixed $team = null
    ): static;

    /**
     * Sync permissions to the user.
     */
    public function syncPermissions(
        array $permissions = [],
        mixed $team = null,
        bool $detaching = true
    ): static;

    /**
     * Return all the user permissions.
     *
     * @return Collection<\Laratrust\Contracts\Permission>
     */
    public function allPermissions(array $columns = null, $team = false): Collection;
}
