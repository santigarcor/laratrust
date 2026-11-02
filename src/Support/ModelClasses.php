<?php

declare(strict_types=1);

namespace Laratrust\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Contracts\Permission;
use Laratrust\Contracts\Role;
use Laratrust\Contracts\Team;

/**
 * The model classes configured in "laratrust.models" and "laratrust.user_models",
 * checked, so a misconfigured class fails with a clear message.
 *
 * @internal
 */
final class ModelClasses
{
    /**
     * @return class-string<Model&Role>
     */
    public static function role(): string
    {
        return self::resolve('laratrust.models.role', Role::class);
    }

    /**
     * @return class-string<Model&Permission>
     */
    public static function permission(): string
    {
        return self::resolve('laratrust.models.permission', Permission::class);
    }

    /**
     * @return class-string<Model&Team>
     */
    public static function team(): string
    {
        return self::resolve('laratrust.models.team', Team::class);
    }

    /**
     * Get the user model configured for the given key, e.g. "users".
     *
     * @return class-string<Model&LaratrustUser>
     */
    public static function user(string $relationship): string
    {
        return self::resolve("laratrust.user_models.{$relationship}", LaratrustUser::class);
    }

    /**
     * Get the keys of the configured user models, e.g. ["users"].
     *
     * @return list<string>
     */
    public static function userRelationships(): array
    {
        return array_map(strval(...), array_keys(Config::get('laratrust.user_models', [])));
    }

    /**
     * @template TContract of object
     *
     * @param class-string<TContract> $contract
     *
     * @return class-string<Model&TContract>
     */
    private static function resolve(string $key, string $contract): string
    {
        $class = Config::get($key);

        if (!is_string($class) || !is_subclass_of($class, Model::class) || !is_subclass_of($class, $contract)) {
            throw new InvalidArgumentException("The [{$key}] config must be an Eloquent model class that implements {$contract}.");
        }

        return $class;
    }
}
