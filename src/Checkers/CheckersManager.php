<?php

declare(strict_types=1);

namespace Laratrust\Checkers;

use Laratrust\Contracts\Role;
use Illuminate\Support\Facades\Config;
use Laratrust\Contracts\LaratrustUser;
use Illuminate\Database\Eloquent\Model;
use Laratrust\Checkers\Role\RoleChecker;
use Laratrust\Checkers\User\UserChecker;
use Laratrust\Checkers\Role\RoleQueryChecker;
use Laratrust\Checkers\User\UserQueryChecker;
use Laratrust\Checkers\Role\RoleDefaultChecker;
use Laratrust\Checkers\User\UserDefaultChecker;

class CheckersManager
{
    public function __construct(protected LaratrustUser|Role|Model $model)
    {
    }

    /**
     * Return the right checker according to the configuration.
     */
    public function getUserChecker(): UserChecker
    {
        switch (Config::get('laratrust.checker', 'default')) {
            default:
            case 'default':
                return new UserDefaultChecker($this->model);
            case 'query':
                return new UserQueryChecker($this->model);
        }
    }

    /**
     * Return the right checker according to the configuration.
     */
    public function getRoleChecker(): RoleChecker
    {
        switch (Config::get('laratrust.checker', 'default')) {
            case 'query':
                return new RoleQueryChecker($this->model);
            default:
            case 'default':
                return new RoleDefaultChecker($this->model);
        }
    }
}
