<?php

namespace Laratrust\Checkers\User;

use Illuminate\Database\Eloquent\Model;

abstract class LaratrustUserChecker
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $user;

    public function __construct(Model $user)
    {
        $this->user = $user;
    }

    abstract public function currentUserHasRole($name, $team = null, $requireAll = false);

    abstract public function currentUserHasPermission($permission, $team = null, $requireAll = false);

    abstract public function currentUserHasAbility($roles, $permissions, $team = null, $options = []);

    abstract public function currentUserFlushCache();
}
