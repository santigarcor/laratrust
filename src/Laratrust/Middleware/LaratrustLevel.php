<?php

namespace Laratrust\Middleware;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */

use App\Models\defaultmodel;
use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Config;

class LaratrustLevel
{
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
     * @param  $level
     * @param $method
     * @return mixed
     */
    public function handle($request, Closure $next, $level, $method = null)
    {
        $passes = false;
        if(!is_null($method)){
            switch($method){
                case '>=':
                    $passes = $this->auth->user()->level() >= $level;
                    break;
                case '<=':
                    $passes = $this->auth->user()->level() <= $level;
                    break;
                case 'BETWEEN':
                    if(strpos($level,'^') === false || count($split = explode('^',$level)) < 2){
                        break;
                    }
                    $passes = $this->auth->user()->level() >= $split[0]  && $this->auth->user()->level() <= $split[1];
                    break;
            }
        }else{
            $passes = $this->auth->user()->level() == $level;
        }
        if ($this->auth->guest() || !$passes) {
            return call_user_func(
                Config::get('laratrust.middleware_handling', 'abort'),
                Config::get('laratrust.middleware_params', '403')
            );
        }
        return $next($request);
    }
}
