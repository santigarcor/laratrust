<?php

declare(strict_types=1);

namespace Laratrust\Contracts;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Ramsey\Uuid\UuidInterface;

interface Role
{
    /**
     * Morph by Many relationship between the role and the one of the possible user models.
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany;

    /**
     * Many-to-Many relations with the permission model.
     */
    public function permissions(): BelongsToMany;

    /**
     * Checks if the role has a permission by its name.
     *
     * @param  string|array|BackedEnum  $permission  Permission name or array of permission names.
     * @param  bool  $requireAll  All permissions in the array are required.
     */
    public function hasPermission(string|array|BackedEnum $permission, bool $requireAll = false): bool;

    /**
     * Save the given permissions.
     */
    public function syncPermissions(iterable $permissions): static;

    /**
     * Give permission to the role.
     */
    public function givePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static;

    /**
     * Remove the permission from the role.
     */
    public function removePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static;

    /**
     * Give multiple permissions to the role.
     */
    public function givePermissions(iterable $permissions): static;

    /**
     * Detach multiple permissions from current role.
     */
    public function removePermissions(iterable $permissions = null): static;
}
