<?php

declare(strict_types=1);

namespace Laratrust\Middleware;

use BackedEnum;
use Closure;
use Illuminate\Http\Request;

class Role extends LaratrustMiddleware
{
    /**
     * Build the middleware string, e.g. Role::using('a|b', team: 'my-team', requireAll: true, guards: ['api']).
     *
     * @param string|array<string|BackedEnum>|BackedEnum $roles
     * @param string|list<string>                        $guards
     */
    public static function using(
        string|array|BackedEnum $roles,
        mixed $team = null,
        bool $requireAll = false,
        string|array $guards = []
    ): string {
        return static::class.':'.static::parameters([$roles], $team, $requireAll, $guards);
    }

    /**
     * Handle incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $roles,
        string ...$parameters
    ): mixed {
        if (!$this->authorization('roles', $roles, $parameters)) {
            return $this->unauthorized();
        }

        return $next($request);
    }
}
