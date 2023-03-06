<?php

declare(strict_types=1);

namespace Laratrust\Checkers;

use Illuminate\Support\Facades\Config;
use Laratrust\Checkers\Role\RoleChecker;
use Laratrust\Checkers\Role\RoleQueryChecker;
use Laratrust\Checkers\Role\RoleDefaultChecker;
use Laratrust\Checkers\User\LaratrustUserQueryChecker;
use Laratrust\Checkers\User\LaratrustUserDefaultChecker;

class CheckersManager
{
    /**
     * The object in charge of checking the roles and permissions.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Return the right checker according to the configuration.
     *
     * @return \Laratrust\Checkers\LaratrustChecker|void
     */
    public function getUserChecker()
    {
        switch (Config::get('laratrust.checker', 'default')) {
            case 'default':
                return new LaratrustUserDefaultChecker($this->model);
            case 'query':
                return new LaratrustUserQueryChecker($this->model);
        }
    }

    /**
     * Return the right checker according to the configuration.
     */
    public function getRoleChecker():RoleChecker
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
