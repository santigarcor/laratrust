<?php

declare(strict_types=1);

namespace Laratrust;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laratrust\Console\MakePermissionCommand;
use Laratrust\Console\MakeRoleCommand;
use Laratrust\Console\MakeSeederCommand;
use Laratrust\Console\MakeTeamCommand;
use Laratrust\Console\MigrationCommand;
use Laratrust\Console\SetupCommand;
use Laratrust\Console\SetupTeamsCommand;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Contracts\Permission as PermissionContract;
use Laratrust\Contracts\Role as RoleContract;
use Laratrust\Contracts\Team as TeamContract;
use Laratrust\Middleware\Ability;
use Laratrust\Middleware\Permission;
use Laratrust\Middleware\Role;
use Laratrust\Support\ModelClasses;
use Laratrust\Support\PermissionRegistry;

class LaratrustServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->configure();
        $this->offerPublishing();
        $this->registerLaratrust();
        $this->registerCommands();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
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
     * Merge the package configuration.
     */
    protected function configure(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laratrust.php', 'laratrust');
    }

    /**
     * Setup the resource publishing groups for Laratrust.
     */
    protected function offerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([__DIR__.'/../config/laratrust.php' => config_path('laratrust.php')], 'laratrust');
        $this->publishes([__DIR__.'/../config/laratrust_seeder.php' => config_path('laratrust_seeder.php')], 'laratrust-seeder');
        $this->publishes([__DIR__.'/../resources/views/panel' => resource_path('views/vendor/laratrust/panel')], 'laratrust-views');
    }

    /**
     * Register the application bindings.
     */
    protected function registerLaratrust(): void
    {
        $this->app->scoped(PermissionRegistry::class);
        // Scoped too, so long-running workers (Octane) never keep a stale application.
        $this->app->scoped('laratrust', fn (Application $app): Laratrust => new Laratrust($app));
        $this->app->alias('laratrust', Laratrust::class);
    }

    /**
     * Register the Laratrust commands.
     */
    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            MakePermissionCommand::class,
            MakeRoleCommand::class,
            MakeSeederCommand::class,
            MakeTeamCommand::class,
            MigrationCommand::class,
            SetupCommand::class,
            SetupTeamsCommand::class,
        ]);
    }

    /**
     * Use the "laratrust.user_models" keys as the morph map, when enabled.
     */
    protected function useMorphMapForRelationships(): void
    {
        if (Config::get('laratrust.use_morph_map')) {
            Relation::morphMap(Config::get('laratrust.user_models'));
        }
    }

    /**
     * Register a relationship to each user model on the role, permission and team models,
     * named after its key in "laratrust.user_models", e.g. $role->users or Role::with('users').
     */
    protected function registerUserRelationships(): void
    {
        foreach (['role', 'permission', 'team'] as $type) {
            $model = Config::get("laratrust.models.{$type}");

            if (! is_string($model) || ! class_exists($model)) {
                continue;
            }

            foreach (ModelClasses::userRelationships() as $relationship) {
                $model::resolveRelationUsing(
                    $relationship,
                    fn (RoleContract|PermissionContract|TeamContract $model): MorphToMany => $model->getMorphByUserRelation($relationship)
                );
            }
        }
    }

    /**
     * Register the "role", "permission" and "ability" middleware aliases, when enabled.
     */
    protected function registerMiddlewares(): void
    {
        if (! Config::get('laratrust.middleware.register')) {
            return;
        }

        Route::aliasMiddleware('role', Role::class);
        Route::aliasMiddleware('permission', Permission::class);
        Route::aliasMiddleware('ability', Ability::class);
    }

    /**
     * Register the Blade directives.
     */
    protected function registerBladeDirectives(): void
    {
        // Each one also defines @else{name}, @unless{name} and @end{name}, e.g. @elserole and @endrole.
        Blade::if('role', fn (mixed ...$arguments) => app(Laratrust::class)->hasRole(...$arguments));
        Blade::if('permission', fn (mixed ...$arguments) => app(Laratrust::class)->hasPermission(...$arguments));
        Blade::if('ability', fn (mixed ...$arguments) => app(Laratrust::class)->ability(...$arguments));
    }

    /**
     * Register the routes used by the Laratrust admin panel, when enabled.
     */
    protected function registerRoutes(): void
    {
        if (! Config::get('laratrust.panel.register')) {
            return;
        }

        Route::group([
            // Null means any domain.
            'domain' => Config::get('laratrust.panel.domain'),
            'prefix' => Config::get('laratrust.panel.path'),
            'middleware' => Config::get('laratrust.panel.middleware', 'web'),
        ], function (): void {
            Route::redirect('/', '/'.Config::get('laratrust.panel.path').'/roles-assignment');
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register the views used by Laratrust.
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laratrust');
    }

    /**
     * Let Gate checks like $user->can('posts.edit') check the Laratrust permissions, when enabled.
     */
    protected function registerPermissionsToGate(): void
    {
        if (! Config::get('laratrust.permissions_as_gates')) {
            return;
        }

        Gate::before(function (Authorizable $user, string $ability, array $arguments): ?bool {
            if (! $user instanceof LaratrustUser) {
                return null;
            }

            // A boolean argument is requireAll, anything else is the team.
            $team = Arr::first($arguments, fn (mixed $argument): bool => ! is_bool($argument));
            $requireAll = Arr::first($arguments, fn (mixed $argument): bool => is_bool($argument)) ?? false;

            // Null lets the policies and the other gates decide.
            return $user->hasPermission($ability, $team, $requireAll) ?: null;
        });
    }

    /**
     * Register the assets that are publishable for the admin panel to work.
     */
    protected function defineAssetPublishing(): void
    {
        if (! Config::get('laratrust.panel.register')) {
            return;
        }

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/laratrust'),
        ], 'laratrust-assets');
    }
}
