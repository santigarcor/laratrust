<?php

declare(strict_types=1);

namespace Laratrust\Models;

use Laratrust\Helper;
use Ramsey\Uuid\UuidInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Laratrust\Checkers\CheckersManager;
use Laratrust\Checkers\Role\RoleChecker;
use Laratrust\Traits\LaratrustHasEvents;
use Laratrust\Contracts\Role as RoleContract;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Laratrust\Traits\LaratrustDynamicUserRelationsCalls;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model implements RoleContract
{
    use LaratrustHasEvents;
    use LaratrustDynamicUserRelationsCalls;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table;

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
        $flushCache = function (Role $role) {
            $role->flushCache();
        };

        // If the role doesn't use SoftDeletes.
        if (method_exists(static::class, 'restored')) {
            static::restored($flushCache);
        }

        static::deleted($flushCache);
        static::saved($flushCache);

        static::deleting(function ($role) {
            if (method_exists($role, 'bootSoftDeletes') && !$role->forceDeleting) {
                return;
            }

            $role->permissions()->sync([]);

            foreach (array_keys(Config::get('laratrust.user_models')) as $key) {
                $role->$key()->sync([]);
            }
        });
    }

    /**
     * Return the right checker for the role model.
     */
    protected function laratrustRoleChecker() :RoleChecker
    {
        return (new CheckersManager($this))->getRoleChecker();
    }

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

    public function permissions():BelongsToMany
    {
        return $this->belongsToMany(
            Config::get('laratrust.models.permission'),
            Config::get('laratrust.tables.permission_role'),
            Config::get('laratrust.foreign_keys.role'),
            Config::get('laratrust.foreign_keys.permission')
        );
    }

    public function hasPermission(string|array $permission, bool $requireAll = false): bool
    {
        return $this->laratrustRoleChecker()
            ->currentRoleHasPermission($permission, $requireAll);
    }

    public function syncPermissions(iterable $permissions):static
    {
        $mappedPermissions = [];

        foreach ($permissions as $permission) {
            $mappedPermissions[] = Helper::getIdFor($permission, 'permission');
        }

        $changes = $this->permissions()->sync($mappedPermissions);
        $this->flushCache();
        $this->fireLaratrustEvent("permission.synced", [$this, $changes]);

        return $this;
    }

    public function givePermission(array|string|int|Model|UuidInterface $permission): static
    {
        $permission = Helper::getIdFor($permission, 'permission');

        $this->permissions()->attach($permission);
        $this->flushCache();
        $this->fireLaratrustEvent("permission.attached", [$this, $permission]);

        return $this;
    }

    public function removePermission(array|string|int|Model|UuidInterface $permission): static
    {
        $permission = Helper::getIdFor($permission, 'permission');

        $this->permissions()->detach($permission);
        $this->flushCache();
        $this->fireLaratrustEvent("permission.detached", [$this, $permission]);

        return $this;
    }

    public function givePermissions(iterable $permissions): static
    {
        foreach ($permissions as $permission) {
            $this->givePermission($permission);
        }

        return $this;
    }

    public function removePermissions(iterable $permissions = null): static
    {
        if (!$permissions) {
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
    public function flushCache():void
    {
        $this->laratrustRoleChecker()->currentRoleFlushCache();
    }
}
