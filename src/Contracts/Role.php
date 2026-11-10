<?php

declare(strict_types=1);

namespace Laratrust\Contracts;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Ramsey\Uuid\UuidInterface;

/**
 * @phpstan-import-type Assignable from LaratrustUser
 */
interface Role
{
    /**
     * Morph by Many relationship between the role and the one of the possible user models.
     *
     * @return MorphToMany<covariant Model, covariant Model>
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany;

    /**
     * Many-to-Many relations with the permission model.
     *
     * @return BelongsToMany<covariant Model, covariant Model>
     */
    public function permissions(): BelongsToMany;

    /**
     * Checks if the role has a permission by its name.
     *
     * @param  bool  $requireAll  All permissions in the array are required.
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function hasPermission(string|array|BackedEnum $permission, bool $requireAll = false): bool;

    /**
     * Save the given permissions.
     *
     * @param  iterable<Assignable>  $permissions
     */
    public function syncPermissions(iterable $permissions): static;

    /**
     * Give permission to the role.
     *
     * @param  Assignable  $permission
     */
    public function givePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static;

    /**
     * Remove the permission from the role.
     *
     * @param  Assignable  $permission
     */
    public function removePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static;

    /**
     * Give multiple permissions to the role.
     *
     * @param  iterable<Assignable>  $permissions
     */
    public function givePermissions(iterable $permissions): static;

    /**
     * Detach multiple permissions from current role. An empty list removes nothing.
     *
     * @param  iterable<Assignable>  $permissions
     */
    public function removePermissions(iterable $permissions): static;

    /**
     * Flush the role's cached permissions.
     */
    public function flushLaratrustCache(): void;

    /**
     * Get the key used to cache the role's permissions.
     */
    public function laratrustCacheKey(): string;
}
