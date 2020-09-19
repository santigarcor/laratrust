<?php

namespace Laratrust\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Config;

trait LaratrustPermissionTrait
{
    use LaratrustDynamicUserRelationsCalls;

    /**
     * Boots the permission model and attaches event listener to
     * remove the many-to-many records when trying to delete.
     * Will NOT delete any records if the permission model uses soft deletes.
     *
     * @return void|bool
     */
    public static function bootLaratrustPermissionTrait()
    {
        static::deleting(function ($permission) {
            if (!method_exists(Config::get('laratrust.models.permission'), 'bootSoftDeletes')) {
                $permission->roles()->sync([]);
            }
        });

        static::deleting(function ($permission) {
            if (method_exists($permission, 'bootSoftDeletes') && !$permission->forceDeleting) {
                return;
            }

            $permission->roles()->sync([]);

            foreach (array_keys(Config::get('laratrust.user_models')) as $key) {
                $permission->$key()->sync([]);
            }
        });
    }

    /**
     * Many-to-Many relations with role model.
     *
     * @return MorphToMany
     */
    public function roles()
    {

        return $this->morphedByMany(
            config('laratrust.models.role'),
            'model',
            config('laratrust.tables.permission_owner'),
            'model_id',
            config('laratrust.foreign_keys.permission')
        );
    }

    /**
     * Morph by Many relationship between the permission and the one of the possible user models.
     *
     * @param  string  $relationship
     * @return MorphToMany
     */
    public function getMorphByUserRelation($relationship)
    {
        return $this->morphedByMany(
            Config::get('laratrust.user_models')[$relationship],
            'model',
            Config::get('laratrust.tables.permission_user'),
            'model_id',
            Config::get('laratrust.foreign_keys.permission')
        );
    }
}
