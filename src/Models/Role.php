<?php

declare(strict_types=1);

namespace Laratrust\Models;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Laratrust\Checkers\RoleChecker;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Contracts\Role as RoleContract;
use Laratrust\Events\PermissionAdded;
use Laratrust\Events\PermissionRemoved;
use Laratrust\Events\PermissionSynced;
use Laratrust\Support\ModelClasses;
use Laratrust\Support\PermissionRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @property int|string $id
 * @property string $name
 * @property string|null $display_name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @phpstan-import-type Assignable from LaratrustUser
 */
class Role extends Model implements RoleContract
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Get the table from "laratrust.tables.roles", unless the model sets its own.
     */
    public function getTable(): string
    {
        return $this->table ?? Config::get('laratrust.tables.roles');
    }

    protected static function booted(): void
    {
        $flushLaratrustCache = function (Role $role): void {
            $role->flushLaratrustCache();
        };

        // If the role uses SoftDeletes.
        if (method_exists(static::class, 'restored')) {
            static::restored($flushLaratrustCache);
        }

        static::deleted($flushLaratrustCache);
        static::saved($flushLaratrustCache);

        static::deleting(function (self $role): void {
            // Soft deleting keeps the assignments, so the model can be restored with them.
            if (method_exists($role, 'isForceDeleting') && ! $role->isForceDeleting()) {
                return;
            }

            $role->permissions()->detach();

            foreach (ModelClasses::userRelationships() as $key) {
                $role->getMorphByUserRelation($key)->detach();
            }
        });
    }

    /**
     * Return the right checker for the role model.
     */
    protected function laratrustRoleChecker(): RoleChecker
    {
        return new RoleChecker($this);
    }

    /**
     * @return MorphToMany<covariant Model, $this>
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany
    {
        return $this->morphedByMany(
            ModelClasses::user($relationship),
            'user',
            Config::get('laratrust.tables.role_user'),
            Config::get('laratrust.foreign_keys.role'),
            Config::get('laratrust.foreign_keys.user')
        );
    }

    /**
     * @return BelongsToMany<covariant Model, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            ModelClasses::permission(),
            Config::get('laratrust.tables.permission_role'),
            Config::get('laratrust.foreign_keys.role'),
            Config::get('laratrust.foreign_keys.permission')
        );
    }

    /**
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function hasPermission(string|array|BackedEnum $permission, bool $requireAll = false): bool
    {
        return $this->laratrustRoleChecker()
            ->hasPermission($permission, $requireAll);
    }

    /**
     * @param  string|array<string|BackedEnum>|BackedEnum  $permission
     */
    public function doesntHavePermission(string|array|BackedEnum $permission, bool $requireAll = false): bool
    {
        return ! $this->hasPermission($permission, $requireAll);
    }

    /**
     * @param  iterable<Assignable>  $permissions
     */
    public function syncPermissions(iterable $permissions): static
    {
        $registry = app(PermissionRegistry::class);
        $mappedPermissions = [];

        foreach ($permissions as $permission) {
            $mappedPermissions[] = $registry->idFor($permission, 'permission');
        }

        $changes = $this->permissions()->sync($mappedPermissions);
        $this->flushLaratrustCache();
        event(new PermissionSynced($this, $changes));

        return $this;
    }

    /**
     * @param  Assignable  $permission
     */
    public function givePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static
    {
        $permission = app(PermissionRegistry::class)->idFor($permission, 'permission');

        // Giving a permission the role already has does nothing.
        if ($this->permissions()->newPivotStatementForId($permission)->exists()) {
            return $this;
        }

        $this->permissions()->attach($permission);
        $this->flushLaratrustCache();
        event(new PermissionAdded($this, $permission));

        return $this;
    }

    /**
     * @param  Assignable  $permission
     */
    public function removePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static
    {
        $permission = app(PermissionRegistry::class)->idFor($permission, 'permission');

        $this->permissions()->detach($permission);
        $this->flushLaratrustCache();
        event(new PermissionRemoved($this, $permission));

        return $this;
    }

    /**
     * @param  iterable<Assignable>  $permissions
     */
    public function givePermissions(iterable $permissions): static
    {
        foreach ($permissions as $permission) {
            $this->givePermission($permission);
        }

        return $this;
    }

    /**
     * Remove the given permissions. An empty list removes nothing: to remove them all, use syncPermissions([]).
     *
     * @param  iterable<Assignable>  $permissions
     */
    public function removePermissions(iterable $permissions): static
    {
        foreach ($permissions as $permission) {
            $this->removePermission($permission);
        }

        return $this;
    }

    /**
     * Flush the role's cache.
     */
    public function flushLaratrustCache(): void
    {
        $this->laratrustRoleChecker()->flush();
    }

    /**
     * Get the key used to cache the role's permissions.
     *
     * Override it to scope the cache, e.g. per tenant in multi-tenant applications.
     */
    public function laratrustCacheKey(): string
    {
        return "laratrust_role_{$this->getKey()}";
    }
}
