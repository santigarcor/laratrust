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
     * Many-to-Many relations with Group.
     */
    public function groups(): MorphToMany;

    /**
     * Many-to-Many relations with Role.
     */
    public function roles(): MorphToMany;

    /**
     * Many-to-Many relations with Permission.
     */
    public function permissions(): MorphToMany;

    /**
     * Checks if the user is in a group by its name.
     */
    public function isInGroup(
        string|array|BackedEnum $name,
        bool $requireAll = false
    ): bool;

    /**
     * Checks if the user has a role by its name.
     */
    public function hasRole(
        string|array|BackedEnum $name,
        bool $requireAll = false
    ): bool;

    /**
     * Check if user has a permission by its name.
     */
    public function hasPermission(
        string|array|BackedEnum $permission,
        bool $requireAll = false
    ): bool;

    /**
     * Check if user has a permission by its name.
     */
    public function isAbleTo(
        string|array|BackedEnum $permission,
        bool $requireAll = false
    ): bool;

    /**
     * Checks role(s) and permission(s).
     *
     * @param  array  $options  validate_all{true|false} or return_type{boolean|array|both}
     *
     * @throws \InvalidArgumentException
     */
    //TODO: Add groups here too
    public function ability(
        string|array|BackedEnum $roles,
        string|array|BackedEnum $permissions,
        array $options = []
    ): array|bool;

    /**
     * Add the user to a group.
     */
    public function addToGroup(
        array|string|int|Model|UuidInterface|BackedEnum $group
    ): static;

    /**
     * Add a role to the user.
     */
    public function addRole(
        array|string|int|Model|UuidInterface|BackedEnum $role
    ): static;

    /**
     * Remove the user from a group.
     */
    public function removeFromGroup(
        array|string|int|Model|UuidInterface|BackedEnum $group
    ): static;

    /**
     * Remove a role from the user.
     */
    public function removeRole(
        array|string|int|Model|UuidInterface|BackedEnum $role
    ): static;

    /**
     * Add multiple roles to a user.
     */
    public function addRoles(
        array $roles = []
    ): static;

    /**
     * Add user to multiple groups.
     */
    public function addToGroups(
        array $groups = []
    ): static;

    /**
     * Remove multiple roles from a user.
     */
    public function removeRoles(
        array $roles = []
    ): static;

    /**
     * Remove user from multiple groups.
     */
    public function removeFromGroups(
        array $groups = []
    ): static;

    /**
     * Sync roles to the user.
     */
    public function syncRoles(
        array $roles = [],
        bool $detaching = true
    ): static;

    /**
     * Sync groups to the user.
     */
    public function syncGroups(
        array $groups = [],
        bool $detaching = true
    ): static;

    /**
     * Add direct permissions to the user.
     */
    public function givePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission
    ): static;

    /**
     * Remove direct permissions from the user.
     */
    public function removePermission(
        array|string|int|Model|UuidInterface|BackedEnum $permission
    ): static;

    /**
     * Add multiple permissions to the user.
     */
    public function givePermissions(
        array $permissions = []
    ): static;

    /**
     * Remove multiple permissions from the user.
     */
    public function removePermissions(
        array $permissions = []
    ): static;

    /**
     * Sync permissions to the user.
     */
    public function syncPermissions(
        array $permissions = [],
        bool $detaching = true
    ): static;

    /**
     * Return all the user permissions.
     *
     * @return Collection<\Laratrust\Contracts\Permission>
     */
    public function allPermissions(array $columns = null): Collection;
}
