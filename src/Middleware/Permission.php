<?php

namespace Laratrust\Middleware;

use Closure;
use Illuminate\Http\Request;

class Permission extends LaratrustMiddleware
{
    /**
     * Handle incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string|array $permissions,
        ?string $team = null,
        ?string $options = ''
    ) {
        if (! $this->authorization('permissions', $permissions, $team, $options)) {
            return $this->unauthorized();
        }

        return $next($request);
    }
}
