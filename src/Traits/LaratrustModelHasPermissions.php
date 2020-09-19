<?php

namespace Laratrust\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Config;

trait LaratrustModelHasPermissions
{
    use LaratrustDynamicRelationsHandling;
    use LaratrustHasPermissionsScopes;

    /**
     * Boots the role model and attaches event listener to
     * remove the many-to-many records when trying to delete.
     * Will NOT delete any records if the role model uses soft deletes.
     *
     * @return void|bool
     */
    public static function bootLaratrustModelHasPermissions()
    {
        $flushCache = function ($model) {
            $model->flushCache();
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

        });
    }
    /**
     * Many-to-Many relations with Permission.
     *
     * @return MorphToMany
     */
    public function permissions()
    {
        $permissions = $this->morphToMany(
            config('laratrust.models.permission'),
            'model',
            config('laratrust.tables.permission_owner'),
            'model_id',
            config('laratrust.foreign_keys.permission')
        );

        if (config('laratrust.teams.enabled') /*&& get_class($this) !== config('laratrust.models.team')*/) {
            $permissions->withPivot(Config::get('laratrust.foreign_keys.team'));
        }

        return $permissions;
    }


    /**
     * Attach multiple permissions to a user.
     *
     * @param  mixed  $permissions
     * @param  mixed  $team
     * @return static
     */
    public function attachPermissions($permissions = [], $team = null)
    {
        foreach ($permissions as $permission) {
            $this->attachPermission($permission, $team);
        }

        return $this;
    }


    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param  mixed  $permission
     * @param  mixed  $team
     * @return static
     */
    public function attachPermission($permission, $team = null)
    {
        return $this->attachModel('permissions', $permission, $team);
    }

    /**
     * Alias to eloquent many-to-many relation's detach() method.
     *
     * @param  mixed  $permission
     * @param  mixed  $team
     * @return static
     */
    public function detachPermission($permission, $team = null)
    {
        return $this->detachModel('permissions', $permission, $team);
    }


    /**
     * Detach multiple permissions from a user.
     *
     * @param  mixed  $permissions
     * @param  mixed  $team
     * @return static
     */
    public function detachPermissions($permissions = [], $team = null)
    {
        if (!$permissions) {
            $permissions = $this->permissions()->get();
        }

        foreach ($permissions as $permission) {
            $this->detachPermission($permission, $team);
        }

        return $this;
    }

    /**
     * Sync permissions to the user.
     *
     * @param  array  $permissions
     * @param  mixed  $team
     * @param  boolean  $detaching
     * @return static
     */
    public function syncPermissions($permissions = [], $team = null, $detaching = true)
    {
        return $this->syncModels('permissions', $permissions, $team, $detaching);
    }

    /**
     * Sync permissions to the user without detaching.
     *
     * @param  array  $permissions
     * @param  mixed  $team
     * @return static
     */
    public function syncPermissionsWithoutDetaching($permissions = [], $team = null)
    {
        return $this->syncPermissions($permissions, $team, false);
    }


}
