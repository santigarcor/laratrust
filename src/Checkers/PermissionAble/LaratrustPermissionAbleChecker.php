<?php

namespace Laratrust\Checkers\PermissionAble;

use Illuminate\Database\Eloquent\Model;
use Laratrust\Traits\LaratrustModelHasPermissions;

abstract class LaratrustPermissionAbleChecker
{
    /**
     * @var Model|LaratrustModelHasPermissions
     */
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Checks if the role has a permission by its name.
     *
     * @param  string|array  $permission  Permission name or array of permission names.
     * @param  null  $team
     * @param  bool  $requireAll  All permissions in the array are required.
     * @param  callable|null  $callback
     * @return bool
     */
    abstract public function currentModelHasPermission($permission, $team = null, $requireAll = false, callable $callback = null);

    /**
     * Flush the model's permissions cache.
     *
     * @return void
     */
    public function currentModelFlushCache()
    {
    }


}
