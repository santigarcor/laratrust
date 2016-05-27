<?php

namespace Santigarcor\Laratrust;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Zizaco\Laratrust
 */

use Illuminate\Support\ServiceProvider;

class LaratrustServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('laratrust.php'),
        ]);

        // Register commands
        $this->commands('command.laratrust.migration');
        
        // Register blade directives
        $this->bladeDirectives();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLaratrust();

        $this->registerCommands();

        $this->mergeConfig();
    }

    /**
     * Register the blade directives
     *
     * @return void
     */
    private function bladeDirectives()
    {
        // Call to Laratrust::hasRole
        \Blade::directive('role', function ($expression) {
            return "<?php if (\\Laratrust::hasRole{$expression}) : ?>";
        });

        \Blade::directive('endrole', function ($expression) {
            return "<?php endif; // Laratrust::hasRole ?>";
        });

        // Call to Laratrust::can
        \Blade::directive('permission', function ($expression) {
            return "<?php if (\\Laratrust::can{$expression}) : ?>";
        });

        \Blade::directive('endpermission', function ($expression) {
            return "<?php endif; // Laratrust::can ?>";
        });

        // Call to Laratrust::ability
        \Blade::directive('ability', function ($expression) {
            return "<?php if (\\Laratrust::ability{$expression}) : ?>";
        });

        \Blade::directive('endability', function ($expression) {
            return "<?php endif; // Laratrust::ability ?>";
        });
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    private function registerLaratrust()
    {
        $this->app->bind('laratrust', function ($app) {
            return new Laratrust($app);
        });
        
        $this->app->alias('laratrust', 'Santigarcor\Laratrust\Laratrust');
    }

    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->app->singleton('command.laratrust.migration', function ($app) {
            return new MigrationCommand();
        });
    }

    /**
     * Merges user's and laratrust's configs.
     *
     * @return void
     */
    private function mergeConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/config.php',
            'laratrust'
        );
    }

    /**
     * Get the services provided.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'command.laratrust.migration'
        ];
    }
}
