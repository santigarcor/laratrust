<?php

declare(strict_types=1);

namespace Laratrust\Contracts;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Ramsey\Uuid\UuidInterface;

interface Group
{
    /**
     * Morph by Many relationship between the group and the one of the possible user models.
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany;

    /**
     * Many-to-Many relations with the permission model.
     */
    public function permissions(): BelongsToMany;

    /**
     * Many-to-Many relations with the role model.
     */
    public function roles(): BelongsToMany;

    /**
     * Checks if the group has a permission by its name.
     *
     * @param  string|array|BackedEnum  $permission  Permission name or array of permission names.
     * @param  bool  $requireAll  All permissions in the array are required.
     */
    public function hasPermission(string|array|BackedEnum $permission, bool $requireAll = false): bool;

    /**
     * Checks if the group has a role by its name.
     *
     * @param  string|array|BackedEnum  $role Role name or array of role names.
     * @param  bool  $requireAll  All roles in the array are required.
     */
    public function hasRole(string|array|BackedEnum $role, bool $requireAll = false): bool;

    /**
     * Save the given permissions.
     */
    public function syncPermissions(iterable $permissions): static;

    /**
     * Save the given roles.
     */
    public function syncRoles(iterable $roles): static;

    /**
     * Give permission to the group.
     */
    public function givePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static;

    /**
     * Add role to the group.
     */
    public function addRole(array|string|int|Model|UuidInterface|BackedEnum $role): static;

    /**
     * Remove the permission from the group.
     */
    public function removePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static;

    /**
     * Remove the role from the group.
     */
    public function removeRole(array|string|int|Model|UuidInterface|BackedEnum $role): static;

    /**
     * Give multiple permissions to the group.
     */
    public function givePermissions(iterable $permissions): static;

    /**
     * Add multiple roles to the group.
     */
    public function addRoles(iterable $roles): static;

    /**
     * Detach multiple permissions from current group.
     */
    public function removePermissions(iterable $permissions = null): static;

    /**
     * Detach multiple roles from current group.
     */
    public function removeRoles(iterable $roles = null): static;
}
