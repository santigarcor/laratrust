<?php

declare(strict_types=1);

namespace Laratrust\Traits;

use Closure;
use Illuminate\Support\Str;

trait HasLaratrustEvents
{
    protected static array $laratrustObservables = [
        'roleAdded',
        'roleRemoved',
        'permissionAdded',
        'permissionRemoved',
        'roleSynced',
        'permissionSynced',
    ];

    /**
     * Register an observer to the Laratrust events.
     */
    public static function laratrustObserve(object|string $class): void
    {
        $className = is_string($class) ? $class : get_class($class);

        foreach (self::$laratrustObservables as $event) {
            if (method_exists($class, $event)) {
                static::registerLaratrustEvent(Str::snake($event, '.'), $className.'@'.$event);
            }
        }
    }

    public static function laratrustFlushObservables()
    {
        foreach (self::$laratrustObservables as $event) {
            $event = Str::snake($event, '.');
            static::$dispatcher->forget("laratrust.{$event}: ".static::class);
        }
    }

    /**
     * Fire the given event for the model.
     */
    protected function fireLaratrustEvent(string $event, array $payload)
    {
        if (! isset(static::$dispatcher)) {
            return true;
        }

        return static::$dispatcher->dispatch(
            "laratrust.{$event}: ".static::class,
            $payload
        );
    }

    /**
     * Register a laratrust event with the dispatcher.
     */
    public static function registerLaratrustEvent(
        string $event,
        Closure|string|array $callback
    ): void {
        if (isset(static::$dispatcher)) {
            $name = static::class;

            static::$dispatcher->listen("laratrust.{$event}: {$name}", $callback);
        }
    }

    /**
     * Register a role added laratrust event with the dispatcher.
     */
    public static function roleAdded(Closure|string|array $callback): void
    {
        static::registerLaratrustEvent('role.added', $callback);
    }

    /**
     * Register a role removed laratrust event with the dispatcher.
     */
    public static function roleRemoved(Closure|string|array $callback): void
    {
        static::registerLaratrustEvent('role.removed', $callback);
    }

    /**
     * Register a permission added laratrust event with the dispatcher.
     */
    public static function permissionAdded(\Closure|string|array $callback): void
    {
        static::registerLaratrustEvent('permission.added', $callback);
    }

    /**
     * Register a permission removed laratrust event with the dispatcher.
     */
    public static function permissionRemoved(\Closure|string|array $callback): void
    {
        static::registerLaratrustEvent('permission.removed', $callback);
    }

    /**
     * Register a role synced laratrust event with the dispatcher.
     */
    public static function roleSynced(\Closure|string|array $callback): void
    {
        static::registerLaratrustEvent('role.synced', $callback);
    }

    /**
     * Register a permission synced laratrust event with the dispatcher.
     */
    public static function permissionSynced(\Closure|string|array $callback): void
    {
        static::registerLaratrustEvent('permission.synced', $callback);
    }
}
