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
use Laratrust\Contracts\Role as RoleContract;
use Laratrust\Events\PermissionAdded;
use Laratrust\Events\PermissionRemoved;
use Laratrust\Events\PermissionSynced;
use Laratrust\Helper;
use Ramsey\Uuid\UuidInterface;

/**
 * @property int|string $id
 * @property string $name
 * @property string|null $display_name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Role extends Model implements RoleContract
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Creates a new instance of the model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Config::get('laratrust.tables.roles');
    }

    protected static function booted(): void
    {
        $flushLaratrustCache = function (Role $role) {
            $role->flushLaratrustCache();
        };

        // If the role doesn't use SoftDeletes.
        if (method_exists(static::class, 'restored')) {
            static::restored($flushLaratrustCache);
        }

        static::deleted($flushLaratrustCache);
        static::saved($flushLaratrustCache);

        static::deleting(function ($role) {
            if (method_exists($role, 'bootSoftDeletes') && ! $role->forceDeleting) {
                return;
            }

            $role->permissions()->detach();

            foreach (array_keys(Config::get('laratrust.user_models')) as $key) {
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
     * @return MorphToMany<Model, $this>
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany
    {
        return $this->morphedByMany(
            Config::get('laratrust.user_models')[$relationship],
            'user',
            Config::get('laratrust.tables.role_user'),
            Config::get('laratrust.foreign_keys.role'),
            Config::get('laratrust.foreign_keys.user')
        );
    }

    /**
     * @return BelongsToMany<Model, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Config::get('laratrust.models.permission'),
            Config::get('laratrust.tables.permission_role'),
            Config::get('laratrust.foreign_keys.role'),
            Config::get('laratrust.foreign_keys.permission')
        );
    }

    public function hasPermission(string|array|BackedEnum $permission, bool $requireAll = false): bool
    {
        return $this->laratrustRoleChecker()
            ->hasPermission($permission, $requireAll);
    }

    public function doesntHavePermission(string|array|BackedEnum $permission, bool $requireAll = false): bool
    {
        return ! $this->hasPermission($permission, $requireAll);
    }

    public function syncPermissions(iterable $permissions): static
    {
        $mappedPermissions = [];

        foreach ($permissions as $permission) {
            $mappedPermissions[] = Helper::getIdFor($permission, 'permission');
        }

        $changes = $this->permissions()->sync($mappedPermissions);
        $this->flushLaratrustCache();
        event(new PermissionSynced($this, $changes));

        return $this;
    }

    public function givePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static
    {
        $permission = Helper::getIdFor($permission, 'permission');

        $this->permissions()->attach($permission);
        $this->flushLaratrustCache();
        event(new PermissionAdded($this, $permission));

        return $this;
    }

    public function removePermission(array|string|int|Model|UuidInterface|BackedEnum $permission): static
    {
        $permission = Helper::getIdFor($permission, 'permission');

        $this->permissions()->detach($permission);
        $this->flushLaratrustCache();
        event(new PermissionRemoved($this, $permission));

        return $this;
    }

    public function givePermissions(iterable $permissions): static
    {
        foreach ($permissions as $permission) {
            $this->givePermission($permission);
        }

        return $this;
    }

    public function removePermissions(?iterable $permissions = null): static
    {
        if (! $permissions) {
            $this->syncPermissions([]);

            return $this;
        }

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
