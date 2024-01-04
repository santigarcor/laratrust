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
        ?string $options = ''
    ) {
        if (!$this->authorization('permissions', $permissions, $options)) {
            return $this->unauthorized();
        }

        return $next($request);
    }
}
