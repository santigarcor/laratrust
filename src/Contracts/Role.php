<?php

declare(strict_types=1);

namespace Laratrust\Contracts;

use Ramsey\Uuid\UuidInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
     * @param string|array $permission Permission name or array of permission names.
     * @param bool $requireAll All permissions in the array are required.
     */
    public function hasPermission(string|array $permission, bool $requireAll = false):bool;

    /**
     * Save the given permissions.
     */
    public function syncPermissions(iterable $permissions):static;

    /**
     * Attach permission to current role.
     */
    public function attachPermission(array|string|int|Model|UuidInterface $permission):static;

    /**
     * Detach permission from current role.
     */
    public function detachPermission(array|string|int|Model|UuidInterface $permission):static;

    /**
     * Attach multiple permissions to current role.
     */
    public function attachPermissions(iterable $permissions):static;

    /**
     * Detach multiple permissions from current role
     */
    public function detachPermissions(iterable $permissions):static;

}
