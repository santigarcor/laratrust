<?php

namespace Laratrust\Traits;

use Illuminate\Support\Facades\Config;
use Laratrust\Checkers\LaratrustCheckerManager;
use Laratrust\Checkers\PermissionAble\LaratrustPermissionAbleChecker;

trait LaratrustTeamTrait
{
    use LaratrustHasEvents;
    use LaratrustDynamicUserRelationsCalls;
    use LaratrustModelHasPermissions;


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
            Config::get('laratrust.foreign_keys.team'),
            Config::get('laratrust.foreign_keys.user')
        );
    }


    /**
     * Return the right checker for the role model.
     *
     * @return LaratrustPermissionAbleChecker
     */
    protected function laratrustPermissionChecker()
    {
        return (new LaratrustCheckerManager($this))->getPermissionsChecker();
    }

    /**
     * Checks if the eam has a permission by its name.
     *
     * @inheritDoc
     */
    public function hasPermission($permission, $requireAll = false)
    {
        return $this->laratrustPermissionChecker()
            ->currentModelHasPermission($permission, $requireAll);
    }

}
