<?php

namespace Laratrust\Middleware;

use Closure;
use Illuminate\Http\Request;

class Role extends LaratrustMiddleware
{
    /**
     * Handle incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string|array $roles,
        ?string $team = null,
        ?string $options = ''
    ) {
        if (! $this->authorization('roles', $roles, $team, $options)) {
            return $this->unauthorized();
        }

        return $next($request);
    }
}
