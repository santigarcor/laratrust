<?php

declare(strict_types=1);

namespace Laratrust\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Contracts\Permission as PermissionContract;
use Laratrust\Contracts\Role as RoleContract;
use Laratrust\Support\ModelClasses;
use Laratrust\Support\PermissionRegistry;

/**
 * @property int|string $id
 * @property string $name
 * @property string|null $display_name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Permission extends Model implements PermissionContract
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Get the table from "laratrust.tables.permissions", unless the model sets its own.
     */
    public function getTable(): string
    {
        return $this->table ?? Config::get('laratrust.tables.permissions');
    }

    protected static function booted(): void
    {
        $forgetIds = fn () => app(PermissionRegistry::class)->forgetIds('permission');
        static::saved($forgetIds);
        static::deleted($forgetIds);

        // The roles and users cache the permission by its name.
        static::saved(function (self $permission): void {
            if ($permission->wasChanged('name')) {
                $permission->flushLaratrustCache();
            }
        });

        // If the permission uses SoftDeletes.
        if (method_exists(static::class, 'restored')) {
            static::restored(function (self $permission): void {
                $permission->flushLaratrustCache();
            });
        }

        static::deleting(function (self $permission): void {
            // Soft deleted permissions are left out too. Flushed first, as the pivot rows tell who has the permission.
            $permission->flushLaratrustCache();

            // Soft deleting keeps the assignments, so the model can be restored with them.
            if (method_exists($permission, 'isForceDeleting') && ! $permission->isForceDeleting()) {
                return;
            }

            $permission->roles()->detach();

            foreach (ModelClasses::userRelationships() as $key) {
                $permission->getMorphByUserRelation($key)->detach();
            }
        });
    }

    /**
     * Many-to-Many relations with role model.
     *
     * @return BelongsToMany<covariant Model, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            ModelClasses::role(),
            Config::get('laratrust.tables.permission_role'),
            Config::get('laratrust.foreign_keys.permission'),
            Config::get('laratrust.foreign_keys.role')
        );
    }

    /**
     * Morph by Many relationship between the permission and the one of the possible user models.
     *
     * @return MorphToMany<covariant Model, $this>
     */
    public function getMorphByUserRelation(string $relationship): MorphToMany
    {
        return $this->morphedByMany(
            ModelClasses::user($relationship),
            'user',
            Config::get('laratrust.tables.permission_user'),
            Config::get('laratrust.foreign_keys.permission'),
            Config::get('laratrust.foreign_keys.user')
        );
    }

    /**
     * Flush the cache of the roles and users that have the permission.
     */
    public function flushLaratrustCache(): void
    {
        $withThisPermission = fn (Builder $permissions): Builder => $permissions->whereKey($this->getKey());

        ModelClasses::role()::query()
            ->whereHas('permissions', $withThisPermission)
            ->each(fn (Model&RoleContract $role) => $role->flushLaratrustCache());

        foreach (ModelClasses::userRelationships() as $key) {
            ModelClasses::user($key)::query()
                ->whereHas('permissions', $withThisPermission)
                ->each(fn (Model&LaratrustUser $user) => $user->flushLaratrustCache());
        }
    }
}
