<?php

namespace Laratrust\Traits;

trait LaratrustHasEvents
{
    /**
     * Register an observer to the Laratrust events.
     *
     * @param  object|string  $class
     * @return void
     */
    public static function laratrustObserve($class)
    {
        $observables = [
            'roleAttached',
            'roleDetached',
            'permissionAttached',
            'permissionDetached',
            'roleSynced',
            'permissionSynced',
        ];

        $className = is_string($class) ? $class : get_class($class);

        foreach ($observables as $event) {
            if (method_exists($class, $event)) {
                static::registerLaratrustEvent(\Illuminate\Support\Str::snake($event, '.'), $className.'@'.$event);
            }
        }
    }

    /**
     * Fire the given event for the model.
     *
     * @param  string  $event
     * @param  array  $payload
     * @return mixed
     */
    protected function fireLaratrustEvent($event, array $payload)
    {
        if (! isset(static::$dispatcher)) {
            return true;
        }

        return static::$dispatcher->fire(
            "laratrust.{$event}: ".static::class,
            $payload
        );
    }

    /**
     * Register a laratrust event with the dispatcher.
     *
     * @param  string  $event
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function registerLaratrustEvent($event, $callback)
    {
        if (isset(static::$dispatcher)) {
            $name = static::class;

            static::$dispatcher->listen("laratrust.{$event}: {$name}", $callback);
        }
    }

    /**
     * Register a role attached laratrust event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function roleAttached($callback)
    {
        static::registerLaratrustEvent('role.attached', $callback);
    }

    /**
     * Register a role detached laratrust event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function roleDetached($callback)
    {
        static::registerLaratrustEvent('role.detached', $callback);
    }

    /**
     * Register a permission attached laratrust event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function permissionAttached($callback)
    {
        static::registerLaratrustEvent('permission.attached', $callback);
    }

    /**
     * Register a permission detached laratrust event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function permissionDetached($callback)
    {
        static::registerLaratrustEvent('permission.detached', $callback);
    }

    /**
     * Register a role synced laratrust event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function roleSynced($callback)
    {
        static::registerLaratrustEvent('role.synced', $callback);
    }

    /**
     * Register a permission synced laratrust event with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function permissionSynced($callback)
    {
        static::registerLaratrustEvent('permission.synced', $callback);
    }
}
