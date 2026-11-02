<?php

declare(strict_types=1);

namespace Laratrust\Middleware;

use BackedEnum;
use Closure;
use Illuminate\Http\Request;

class Permission extends LaratrustMiddleware
{
    /**
     * Build the middleware string, e.g. Permission::using('a|b', team: 'my-team', requireAll: true, guards: ['api']).
     *
     * @param string|array<string|BackedEnum>|BackedEnum $permissions
     * @param string|list<string>                        $guards
     */
    public static function using(
        string|array|BackedEnum $permissions,
        mixed $team = null,
        bool $requireAll = false,
        string|array $guards = []
    ): string {
        return static::class.':'.static::parameters([$permissions], $team, $requireAll, $guards);
    }

    /**
     * Handle incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $permissions,
        string ...$parameters
    ): mixed {
        if (!$this->authorization('permissions', $permissions, $parameters)) {
            return $this->unauthorized();
        }

        return $next($request);
    }
}
