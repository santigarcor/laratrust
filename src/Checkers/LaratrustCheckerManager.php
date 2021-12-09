<?php

namespace Laratrust\Checkers;

use Illuminate\Support\Facades\Config;
use Laratrust\Checkers\Role\LaratrustRoleQueryChecker;
use Laratrust\Checkers\User\LaratrustUserQueryChecker;
use Laratrust\Contracts\LaratrustRoleCheckerInterface;
use Laratrust\Contracts\LaratrustUserCheckerInterface;
use Laratrust\Checkers\Role\LaratrustRoleDefaultChecker;
use Laratrust\Checkers\User\LaratrustUserDefaultChecker;

class LaratrustCheckerManager
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
     * @return \Laratrust\Checkers\User\LaratrustUserChecker|LaratrustUserCheckerInterface
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getUserChecker()
    {
        $checker = Config::get('laratrust.user_checker', 'default');
        switch ($checker) {
            case 'default':
                return new LaratrustUserDefaultChecker($this->model);
            case 'query':
                return new LaratrustUserQueryChecker($this->model);
            default:
                if (!is_a($checker, LaratrustUserCheckerInterface::class, true)) {
                    throw new \RuntimeException("User checker must implement LaratrustUserCheckerInterface");
                }
                return app()->make($checker, ['user' => $this->model]);
        }
    }

    /**
     * Return the right checker according to the configuration.
     *
     * @return \Laratrust\Checkers\Role\LaratrustRoleChecker|LaratrustRoleCheckerInterface
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getRoleChecker()
    {
        $checker = Config::get('laratrust.role_checker', 'default');
        switch ($checker) {
            case 'default':
                return new LaratrustRoleDefaultChecker($this->model);
            case 'query':
                return new LaratrustRoleQueryChecker($this->model);
            default:
                if (!is_a($checker, LaratrustRoleCheckerInterface::class, true)) {
                    throw new \RuntimeException("Role checker must implement LaratrustRoleCheckerInterface");
                }
                return app()->make($checker, ['role' => $this->model]);
        }
    }
}
