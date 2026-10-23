<?php

declare(strict_types=1);

namespace Laratrust\Middleware;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laratrust\Contracts\LaratrustUser;

/**
 * Commas separate the parameters and pipes separate the values within one, like in "role:admin|editor".
 * After the names come an optional team and the options: "require_all" and "guard:{name}|{name}".
 * For example:
 * - "role:admin|editor,my-team,require_all,guard:web|admin"
 * - "permission:posts.edit,guard:web|admin"
 *
 * The 8.x forms also work: "require_all|guard:web|guard:admin".
 */
class LaratrustMiddleware
{
    /**
     * Check if the request has authorization to continue.
     */
    /**
     * @param  array<array-key, string>  $parameters  The middleware parameters after the names.
     */
    protected function authorization(string $type, string $rolesPermissions, array $parameters): bool
    {
        ['team' => $team, 'require_all' => $requireAll, 'guards' => $guards] = $this->getValuesFromParameters($parameters);
        $method = $type == 'roles' ? 'hasRole' : 'hasPermission';
        $user = $this->authenticatedUser($guards);

        return $user !== null && $user->$method(explode('|', $rolesPermissions), $team, $requireAll);
    }

    /**
     * The request is unauthorized, so it handles the aborting/redirecting.
     */
    protected function unauthorized(): mixed
    {
        $handling = Config::get('laratrust.middleware.handling');
        $handler = Config::get("laratrust.middleware.handlers.{$handling}");

        // The messages can be translation keys.
        if ($handling == 'abort') {
            return App::abort($handler['code'], __($handler['message'] ?? 'User does not have any of the necessary access rights.'));
        }

        $redirect = Redirect::to($handler['url']);
        if (! empty($handler['message']['content'])) {
            $redirect->with($handler['message']['key'], __($handler['message']['content']));
        }

        return $redirect;
    }

    /**
     * Get the user of the first given guard that is authenticated, like Laravel's "auth" middleware.
     *
     * @param  array<int, string|null>  $guards
     */
    protected function authenticatedUser(array $guards): ?LaratrustUser
    {
        foreach ($guards as $guard) {
            $auth = Auth::guard($guard);

            if (! $auth->guest()) {
                $user = $auth->user();

                return $user instanceof LaratrustUser ? $user : null;
            }
        }

        return null;
    }

    /**
     * Get the team, requireAll and guards from the middleware parameters after the names.
     *
     * @param  array<array-key, string>  $parameters
     * @return array{team: string|null, require_all: bool, guards: array<int, string|null>}
     *
     * @throws InvalidArgumentException When a parameter doesn't fit, so a mistyped team is never ignored.
     */
    protected function getValuesFromParameters(array $parameters): array
    {
        $team = null;
        $requireAll = false;
        $guards = [];
        $inOptions = false;
        $inGuards = false;

        foreach ($parameters as $parameter) {
            foreach (explode('|', $parameter) as $option) {
                if ($option === '') {
                    continue;
                }

                if ($option === 'require_all') {
                    [$requireAll, $inOptions, $inGuards] = [true, true, false];
                } elseif (str_starts_with($option, 'guard:')) {
                    $guards[] = Str::after($option, 'guard:');
                    [$inOptions, $inGuards] = [true, true];
                } elseif ($inGuards) {
                    $guards[] = $option;
                } elseif (! $inOptions && $team === null) {
                    $team = $option;
                } else {
                    throw new InvalidArgumentException(
                        "Unexpected Laratrust middleware parameter [{$option}]. The team must come before the options."
                    );
                }
            }
        }

        return [
            'team' => $team,
            'require_all' => $requireAll,
            'guards' => $guards === [] ? [Config::get('auth.defaults.guard')] : $guards,
        ];
    }

    /**
     * Build the "names,team,options" parameters string, used by the using() helpers.
     *
     * @param  array<int, string|array<string|BackedEnum>|BackedEnum>  $names  Each one is a parameter.
     * @param  string|list<string>  $guards
     */
    protected static function parameters(
        array $names,
        mixed $team,
        bool $requireAll,
        string|array $guards
    ): string {
        $guards = Arr::wrap($guards);
        $options = Collection::make($requireAll ? ['require_all'] : [])
            ->when($guards !== [], fn (Collection $options) => $options->push('guard:'.implode('|', $guards)))
            ->implode(',');

        $team = match (true) {
            $team instanceof Model => $team->getKey(),
            $team instanceof BackedEnum => $team->value,
            default => $team,
        };

        return Collection::make($names)
            ->map(fn (string|array|BackedEnum $value) => Collection::make(Arr::wrap($value))
                ->map(fn (string|BackedEnum $name) => $name instanceof BackedEnum ? $name->value : $name)
                ->implode('|'))
            ->when($team !== null, fn (Collection $parameters) => $parameters->push((string) $team))
            ->when($options !== '', fn (Collection $parameters) => $parameters->push($options))
            ->implode(',');
    }
}
