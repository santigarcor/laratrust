<?php

declare(strict_types=1);

namespace Laratrust\Middleware;

use BackedEnum;
use Closure;
use Illuminate\Http\Request;

class Ability extends LaratrustMiddleware
{
    /**
     * Build the middleware string, e.g. Ability::using('admin', 'posts.edit', team: 'my-team', validateAll: true).
     *
     * @param  string|array<string|BackedEnum>|BackedEnum  $roles
     * @param  string|array<string|BackedEnum>|BackedEnum  $permissions
     * @param  string|list<string>  $guards
     */
    public static function using(
        string|array|BackedEnum $roles,
        string|array|BackedEnum $permissions,
        mixed $team = null,
        bool $validateAll = false,
        string|array $guards = []
    ): string {
        return static::class.':'.static::parameters([$roles, $permissions], $team, $validateAll, $guards);
    }

    /**
     * Handle incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $roles,
        string $permissions,
        string ...$parameters
    ): mixed {
        [
            'team' => $team,
            'require_all' => $validateAll,
            'guards' => $guards,
        ] = $this->getValuesFromParameters($parameters);

        $user = $this->authenticatedUser($guards);

        if ($user === null || ! $user->ability(explode('|', $roles), explode('|', $permissions), $team, ['validate_all' => $validateAll])) {
            return $this->unauthorized();
        }

        return $next($request);
    }
}
