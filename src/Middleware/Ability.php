<?php

namespace Laratrust\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laratrust\Helper;

class Ability extends LaratrustMiddleware
{
    /**
     * Handle incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string|array $roles,
        string|array $permissions,
        ?string $team = null,
        ?string $options = ''
    ) {
        [
            'team' => $team,
            'require_all' => $validateAll,
            'guard' => $guard,
        ] = $this->getValuesFromParameters($team, $options);

        $roles = Helper::standardize($roles, true);
        $permissions = Helper::standardize($permissions, true);

        if (
            Auth::guard($guard)->guest()
            || ! Auth::guard($guard)->user()
                ->ability($roles, $permissions, $team, ['validate_all' => $validateAll])
        ) {
            return $this->unauthorized();
        }

        return $next($request);
    }
}
