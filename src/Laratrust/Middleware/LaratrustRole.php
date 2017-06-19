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

class LaratrustRole
{
    const DELIMITER = '|';

    protected $auth;

    /**
     * Creates a new instance of the middleware.
     *
     * @param  Guard  $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Closure  $next
     * @param  $roles
     * @return mixed
     */
    public function handle($request, Closure $next, $roles, $team = null, $requireAll = false)
    {
        list($team, $requireAll) = $this->assignRealValuesTo($team, $requireAll);

        if (!is_array($roles)) {
            $roles = explode(self::DELIMITER, $roles);
        }

        if ($this->auth->guest() || !$request->user()->hasRole($roles, $team, $requireAll)) {
            return call_user_func(
                Config::get('laratrust.middleware.handling', 'abort'),
                Config::get('laratrust.middleware.params', '403')
            );
        }

        return $next($request);
    }

    /**
     * Assing the real values to the team and requireAllOrOptions parameters.
     *
     * @param  mixed  $team
     * @param  mixed  $requireAllOrOptions
     * @return array
     */
    private function assignRealValuesTo($team, $requireAllOrOptions)
    {
        return [
            ($team == 'require_all' ? null : $team),
            ($team == 'require_all' ? true : ($requireAllOrOptions== 'require_all' ? true : false)),
        ];
    }
}
