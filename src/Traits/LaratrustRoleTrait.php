<?php

namespace Laratrust\Traits;

use Illuminate\Support\Facades\Config;
use Laratrust\Checkers\LaratrustCheckerManager;
use Laratrust\Checkers\PermissionAble\LaratrustPermissionAbleChecker;

trait LaratrustRoleTrait
{
    use LaratrustDynamicUserRelationsCalls;
    use LaratrustHasEvents;
    use LaratrustModelHasPermissions;

    /**
     * Boots the role model and attaches event listener to
     * remove the many-to-many records when trying to delete.
     * Will NOT delete any records if the role model uses soft deletes.
     *
     * @return void|bool
     */
    public static function bootLaratrustRoleTrait()
    {

        static::deleting(function ($role) {
            if (method_exists($role, 'bootSoftDeletes') && !$role->forceDeleting) {
                return;
            }
            foreach (array_keys(Config::get('laratrust.user_models')) as $key) {
                $role->$key()->sync([]);
            }
        });
    }

    /**
     * Return the right checker for the role model.
     *
     * @return LaratrustPermissionAbleChecker
     */
    protected function laratrustRoleChecker()
    {
        return (new LaratrustCheckerManager($this))->getRoleChecker();
    }

    /**
     * Checks if the role has a permission by its name.
     *
     * @inheritDoc
     */
    public function hasPermission($permission, $team = null, $requireAll = false)
    {
        return $this->laratrustRoleChecker()
            ->currentModelHasPermission($permission, $team, $requireAll);
    }


    /**
     * Morph by Many relationship between the role and the one of the possible user models.
     *
     * @param  string  $relationship
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function getMorphByUserRelation($relationship)
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
     * Flush the role's cache.
     *
     * @return void
     */
    public function flushCache()
    {
        return $this->laratrustRoleChecker()->currentModelFlushCache();
    }
}
