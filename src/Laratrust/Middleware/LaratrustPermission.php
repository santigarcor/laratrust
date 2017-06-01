<?php

namespace Laratrust\Middleware;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Config;

class LaratrustPermission
{
    const DELIMITER = '|';

    protected $auth;

    /**
     * Creates a new instance of the middleware.
     *
     * @param Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  Closure $next
     * @param  $permissions
     * @return mixed
     */
    public function handle($request, Closure $next, $permissions, $group = null, $requireAll = false)
    {
        list($group, $requireAll) = $this->assignRealValuesTo($group, $requireAll);

        if (!is_array($permissions)) {
            $permissions = explode(self::DELIMITER, $permissions);
        }

        if ($this->auth->guest() || !$request->user()->hasPermission($permissions, $group, $requireAll)) {
            return call_user_func(
                Config::get('laratrust.middleware_handling', 'abort'),
                Config::get('laratrust.middleware_params', '403')
            );
        }

        return $next($request);
    }

    /**
     * Assing the real values to the group and requireAllOrOptions parameters
     * @param  mixed $group
     * @param  mixed $requireAllOrOptions
     * @return array
     */
    private function assignRealValuesTo($group, $requireAllOrOptions)
    {
        return [
            ($group == 'require_all' ? null : $group),
            ($group == 'require_all' ? true : ($requireAllOrOptions== 'require_all' ? true : false)),
        ];
    }
}
