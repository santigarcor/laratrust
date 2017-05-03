<?php

namespace Laratrust;

/**
 * This file is part of Laratrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Laratrust
 */

use Illuminate\Support\ServiceProvider;
use Illuminate\View\Factory;
use Laratrust\LaratrustRegistersBladeDirectives;

class LaratrustServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'Migration' => 'command.laratrust.migration',
        'MakeRole' => 'command.laratrust.role',
        'MakePermission' => 'command.laratrust.permission',
        'AddLaratrustUserTraitUse' => 'command.laratrust.add-trait',
        'Setup' => 'command.laratrust.setup',
        'MakeSeeder' => 'command.laratrust.seeder',
        'Upgrade' => 'command.laratrust.upgrade',
        'Levels' => 'command.laratrust.levels'
    ];

    /**
     * Bootstrap the application events.
     *
     * @param  Factory $view
     * @return void
     */
    public function boot()
    {
        // Register published configuration.
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('laratrust.php'),
            __DIR__.'/../config/laratrust_seeder.php' => config_path('laratrust_seeder.php'),
        ], 'laratrust');

        if (class_exists('\Blade')) {
            $this->registerBladeDirectives();
        }
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
     * @param  Factory $view
     * @return void
     */
    private function registerBladeDirectives()
    {
        (new LaratrustRegistersBladeDirectives)->handle($this->app->version());
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

        $this->app->alias('laratrust', 'Laratrust\Laratrust');
    }

    /**
     * Register the given commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        foreach (array_keys($this->commands) as $command) {
            $method = "register{$command}Command";

            call_user_func_array([$this, $method], []);
        }

        $this->commands(array_values($this->commands));
    }
    
    protected function registerMigrationCommand()
    {
        $this->app->singleton('command.laratrust.migration', function () {
            return new MigrationCommand();
        });
    }
    
    protected function registerMakeRoleCommand()
    {
        $this->app->singleton('command.laratrust.role', function ($app) {
            return new MakeRoleCommand($app['files']);
        });
    }
    
    protected function registerMakePermissionCommand()
    {
        $this->app->singleton('command.laratrust.permission', function ($app) {
            return new MakePermissionCommand($app['files']);
        });
    }
    
    protected function registerAddLaratrustUserTraitUseCommand()
    {
        $this->app->singleton('command.laratrust.add-trait', function () {
            return new AddLaratrustUserTraitUseCommand();
        });
    }
    
    protected function registerSetupCommand()
    {
        $this->app->singleton('command.laratrust.setup', function () {
            return new SetupCommand();
        });
    }

    protected function registerMakeSeederCommand()
    {
        $this->app->singleton('command.laratrust.seeder', function () {
            return new MakeSeederCommand();
        });
    }

    protected function registerUpgradeCommand()
    {
        $this->app->singleton('command.laratrust.upgrade', function () {
            return new UpgradeCommand();
        });
    }

    protected function registerLevelsCommand()
    {
        $this->app->singleton('command.laratrust.levels', function () {
            return new LevelsCommand();
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
        return array_values($this->commands);
    }
}
