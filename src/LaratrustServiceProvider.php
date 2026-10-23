<?php

declare(strict_types=1);

namespace Laratrust;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laratrust\Contracts\Permission as PermissionContract;
use Laratrust\Contracts\Role as RoleContract;
use Laratrust\Contracts\Team as TeamContract;
use Laratrust\Middleware\Ability;
use Laratrust\Middleware\Permission;
use Laratrust\Middleware\Role;
use Laratrust\Support\PermissionRegistry;

class LaratrustServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->useMorphMapForRelationships();
        $this->registerUserRelationships();
        $this->registerMiddlewares();
        $this->registerBladeDirectives();
        $this->registerRoutes();
        $this->registerResources();
        $this->registerPermissionsToGate();
        $this->defineAssetPublishing();
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
     * Register a relationship to each user model on the role, permission and team models,
     * named after its key in "laratrust.user_models", e.g. $role->users or Role::with('users').
     */
    protected function registerUserRelationships(): void
    {
        $relationships = array_keys($this->app['config']->get('laratrust.user_models', []));

        foreach (['role', 'permission', 'team'] as $type) {
            $model = $this->app['config']->get("laratrust.models.{$type}");

            if (! is_string($model) || ! class_exists($model)) {
                continue;
            }

            foreach ($relationships as $relationship) {
                $model::resolveRelationUsing(
                    $relationship,
                    fn (RoleContract|PermissionContract|TeamContract $model) => $model->getMorphByUserRelation($relationship)
                );
            }
        }
    }

    /**
     * Register the middlewares automatically.
     *
     * @return void
     */
    protected function registerMiddlewares()
    {
        if (! $this->app['config']->get('laratrust.middleware.register')) {
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

        $middlewares = [
            'role' => Role::class,
            'permission' => Permission::class,
            'ability' => Ability::class,
        ];

        foreach ($middlewares as $key => $class) {
            $router->$registerMethod($key, $class);
        }
    }

    /**
     * Register the blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        // Each one also defines @else{name}, @unless{name} and @end{name}, e.g. @elserole and @endrole.
        Blade::if('role', fn (mixed ...$arguments) => app(Laratrust::class)->hasRole(...$arguments));
        Blade::if('permission', fn (mixed ...$arguments) => app(Laratrust::class)->hasPermission(...$arguments));
        Blade::if('ability', fn (mixed ...$arguments) => app(Laratrust::class)->ability(...$arguments));
    }

    /**
     * Register the routes used by the Laratrust admin panel.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        if (! $this->app['config']->get('laratrust.panel.register')) {
            return;
        }

        Route::group([
            'domain' => config('laratrust.panel.domain', (app()->runningInConsole() === false) ? request()->getHost() : 'localhost'),
            'prefix' => config('laratrust.panel.path'),
            'namespace' => 'Laratrust\Http\Controllers',
            'middleware' => config('laratrust.panel.middleware', 'web'),
        ], function () {
            Route::redirect('/', '/'.config('laratrust.panel.path').'/roles-assignment');
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register all the possible views used by Laratrust.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laratrust');
    }

    /**
     * Register permissions to Laravel Gate.
     *
     * @return void
     */
    protected function registerPermissionsToGate()
    {
        if (! $this->app['config']->get('laratrust.permissions_as_gates')) {
            return;
        }

        app(Gate::class)->before(function (Authorizable $user, mixed $ability, $attributes) {
            if (method_exists($user, 'hasPermission')) {
                $team = Collection::make($attributes)
                    ->filter(fn ($attr) => ! is_bool($attr))
                    ->first();
                $requireAll = Collection::make($attributes)
                    ->filter(fn ($attr) => is_bool($attr))
                    ->first() || false;

                return $user->hasPermission($ability, $team, $requireAll) ?: null;
            }
        });
    }

    /**
     * Register the assets that are publishable for the admin panel to work.
     *
     * @return void
     */
    protected function defineAssetPublishing()
    {
        if (! $this->app['config']->get('laratrust.panel.register')) {
            return;
        }

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/laratrust'),
        ], 'laratrust-assets');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->configure();
        $this->offerPublishing();
        $this->registerLaratrust();
        $this->registerCommands();
    }

    /**
     * Setup the configuration for Laratrust.
     *
     * @return void
     */
    protected function configure()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laratrust.php', 'laratrust');
    }

    /**
     * Setup the resource publishing group for Laratrust.
     *
     * @return void
     */
    protected function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laratrust.php' => config_path('laratrust.php'),
            ], 'laratrust');

            $this->publishes([
                __DIR__.'/../config/laratrust_seeder.php' => config_path('laratrust_seeder.php'),
            ], 'laratrust-seeder');

            $this->publishes([
                __DIR__.'/../resources/views/panel' => resource_path('views/vendor/laratrust/panel'),
            ], 'laratrust-views');
        }
    }

    /**
     * Register the application bindings.
     *
     * @return void
     */
    protected function registerLaratrust()
    {
        $this->app->scoped(PermissionRegistry::class);

        $this->app->bind('laratrust', function ($app) {
            return new Laratrust($app);
        });

        $this->app->alias('laratrust', 'Laratrust\Laratrust');
    }

    /**
     * Register the Laratrusts commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\MakePermissionCommand::class,
                Console\MakeRoleCommand::class,
                Console\MakeSeederCommand::class,
                Console\MakeTeamCommand::class,
                Console\MigrationCommand::class,
                Console\SetupCommand::class,
                Console\SetupTeamsCommand::class,
            ]);
        }
    }
}
