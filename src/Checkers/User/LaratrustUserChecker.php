<?php

namespace Laratrust\Checkers\User;

use Illuminate\Database\Eloquent\Model;

abstract class LaratrustUserChecker
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    abstract public function currentModelHasRole($name, $team = null, $requireAll = false);

    abstract public function currentModelHasPermission($permission, $team = null, $requireAll = false);

    abstract public function currentModelHasAbility($roles, $permissions, $team = null, $options = []);

    abstract public function currentModelFlushCache();
}
