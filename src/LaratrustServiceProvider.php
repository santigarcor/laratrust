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
use Illuminate\Database\Eloquent\Relations\Relation;

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
        'MakeTeam' => 'command.laratrust.team',
        'AddLaratrustUserTraitUse' => 'command.laratrust.add-trait',
        'Setup' => 'command.laratrust.setup',
        'SetupTeams' => 'command.laratrust.setup-teams',
        'MakeSeeder' => 'command.laratrust.seeder',
        'Upgrade' => 'command.laratrust.upgrade'
    ];

    /**
     * The middlewares to be registered.
     *
     * @var array
     */
    protected $middlewares = [
        'role' => \Laratrust\Middleware\LaratrustRole::class,
        'permission' => \Laratrust\Middleware\LaratrustPermission::class,
        'ability' => \Laratrust\Middleware\LaratrustAbility::class,
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laratrust.php', 'laratrust');

        $this->publishes([
            __DIR__.'/../config/laratrust.php' => config_path('laratrust.php'),
            __DIR__. '/../config/laratrust_seeder.php' => config_path('laratrust_seeder.php'),
        ], 'laratrust');

        $this->useMorphMapForRelationships();

        $this->registerMiddlewares();

        if (class_exists('\Blade')) {
            $this->registerBladeDirectives();
        }
    }

    /**
     * If the user wants to use the morphMap it uses the morphMap.
     *
     * @return void
     */
    protected function useMorphMapForRelationships()
    {
        if ($this->app['config']->get('laratrust.use_morph_map')) {
            Relation::morphMap($this->app['config']->get('laratrust.user_models'));
        }
    }

    /**
     * Register the middlewares automatically.
     *
     * @return void
     */
    protected function registerMiddlewares()
    {
        if (!$this->app['config']->get('laratrust.middleware.register')) {
            return;
        }

        $router = $this->app['router'];

        if (method_exists($router, 'middleware')) {
            $registerMethod = 'middleware';
        } elseif (method_exists($router, 'aliasMiddleware')) {
            $registerMethod = 'aliasMiddleware';
        } else {
            return;
        }

        foreach ($this->middlewares as $key => $class) {
            $router->$registerMethod($key, $class);
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
    }

    /**
     * Register the blade directives.
     *
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
            return new \Laratrust\Commands\MigrationCommand();
        });
    }

    protected function registerMakeRoleCommand()
    {
        $this->app->singleton('command.laratrust.role', function ($app) {
            return new \Laratrust\Commands\MakeRoleCommand($app['files']);
        });
    }

    protected function registerMakePermissionCommand()
    {
        $this->app->singleton('command.laratrust.permission', function ($app) {
            return new \Laratrust\Commands\MakePermissionCommand($app['files']);
        });
    }

    protected function registerMakeTeamCommand()
    {
        $this->app->singleton('command.laratrust.team', function ($app) {
            return new \Laratrust\Commands\MakeTeamCommand($app['files']);
        });
    }

    protected function registerAddLaratrustUserTraitUseCommand()
    {
        $this->app->singleton('command.laratrust.add-trait', function () {
            return new \Laratrust\Commands\AddLaratrustUserTraitUseCommand();
        });
    }

    protected function registerSetupCommand()
    {
        $this->app->singleton('command.laratrust.setup', function () {
            return new \Laratrust\Commands\SetupCommand();
        });
    }

    protected function registerSetupTeamsCommand()
    {
        $this->app->singleton('command.laratrust.setup-teams', function () {
            return new \Laratrust\Commands\SetupTeamsCommand();
        });
    }

    protected function registerMakeSeederCommand()
    {
        $this->app->singleton('command.laratrust.seeder', function () {
            return new \Laratrust\Commands\MakeSeederCommand();
        });
    }

    protected function registerUpgradeCommand()
    {
        $this->app->singleton('command.laratrust.upgrade', function () {
            return new \Laratrust\Commands\UpgradeCommand();
        });
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
