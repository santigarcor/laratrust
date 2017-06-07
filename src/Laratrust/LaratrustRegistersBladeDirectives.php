<?php

namespace Laratrust;

use Illuminate\Support\Facades\Blade;

/**
 * This class is the one in charge of registering
 * the blade directives making a difference
 * between the version 5.2 and 5.3
 */
class LaratrustRegistersBladeDirectives
{
    /**
     * Handles the registration of the blades directives
     * @param  string $laravelVersion
     * @return void
     */
    public function handle($laravelVersion = '5.3.0')
    {
        if (version_compare(strtolower($laravelVersion), '5.3.0-dev', '>=')) {
            $this->registerWithParenthesis();
        } else {
            $this->registerWithoutParenthesis();
        }

        $this->registerClosingDirectives();
    }

    /**
     * Registers the directives with parenthesis
     * @return void
     */
    protected function registerWithParenthesis()
    {
        // Call to Laratrust::hasRole
        Blade::directive('role', function ($expression) {
            return "<?php if (app('laratrust')->hasRole({$expression})) : ?>";
        });

        // Call to Laratrust::can
        Blade::directive('permission', function ($expression) {
            return "<?php if (app('laratrust')->can({$expression})) : ?>";
        });

        // Call to Laratrust::ability
        Blade::directive('ability', function ($expression) {
            return "<?php if (app('laratrust')->ability({$expression})) : ?>";
        });

        // Call to Laratrust::canAndOwns
        Blade::directive('canAndOwns', function ($expression) {
            return "<?php if (app('laratrust')->canAndOwns({$expression})) : ?>";
        });

        // Call to Laratrust::hasRoleAndOwns
        Blade::directive('hasRoleAndOwns', function ($expression) {
            return "<?php if (app('laratrust')->hasRoleAndOwns({$expression})) : ?>";
        });

        // Call to Laratrust::hasLevelOrGreater
        Blade::directive('hasLevelOrGreater', function ($expression) {
            return "<?php if (app('laratrust')->hasLevelOrGreater({$expression})) : ?>";
        });

        // Call to Laratrust::hasLevelOrLess
        Blade::directive('hasLevelOrLess', function ($expression) {
            return "<?php if (app('laratrust')->hasLevelOrLess({$expression})) : ?>";
        });

        // Call to Laratrust::hasLevelBetween
        Blade::directive('hasLevelBetween', function ($expression) {
            return "<?php if (app('laratrust')->hasLevelBetween({$expression})) : ?>";
        });
    }

    /**
     * Registers the directives without parenthesis
     * @return void
     */
    protected function registerWithoutParenthesis()
    {
        // Call to Laratrust::hasRole
        Blade::directive('role', function ($expression) {
            return "<?php if (app('laratrust')->hasRole{$expression}) : ?>";
        });

        // Call to Laratrust::can
        Blade::directive('permission', function ($expression) {
            return "<?php if (app('laratrust')->can{$expression}) : ?>";
        });

        // Call to Laratrust::ability
        Blade::directive('ability', function ($expression) {
            return "<?php if (app('laratrust')->ability{$expression}) : ?>";
        });

        // Call to Laratrust::canAndOwns
        Blade::directive('canAndOwns', function ($expression) {
            return "<?php if (app('laratrust')->canAndOwns{$expression}) : ?>";
        });

        // Call to Laratrust::hasRoleAndOwns
        Blade::directive('hasRoleAndOwns', function ($expression) {
            return "<?php if (app('laratrust')->hasRoleAndOwns{$expression}) : ?>";
        });

        // Call to Laratrust::hasLevelOrGreater
        Blade::directive('hasLevelOrGreater', function ($expression) {
            return "<?php if (app('laratrust')->hasLevelOrGreater{$expression}): ?>";
        });

        // Call to Laratrust::hasLevelOrLess
        Blade::directive('hasLevelOrLess', function ($expression) {
            return "<?php if (app('laratrust')->hasLevelOrLess{$expression}): ?>";
        });

        // Call to Laratrust::hasLevelBetween
        Blade::directive('hasLevelBetween', function ($expression) {
            return "<?php if (app('laratrust')->hasLevelBetween{$expression}): ?>";
        });
    }

    /**
     * Registers the closing directives
     * @return void
     */
    protected function registerClosingDirectives()
    {
        Blade::directive('endrole', function () {
            return "<?php endif; // app('laratrust')->hasRole ?>";
        });

        Blade::directive('endpermission', function () {
            return "<?php endif; // app('laratrust')->can ?>";
        });

        Blade::directive('endability', function () {
            return "<?php endif; // app('laratrust')->ability ?>";
        });

        Blade::directive('endOwns', function () {
            return "<?php endif; // app('laratrust')->hasRoleAndOwns or canAndOwns ?>";
        });

        Blade::directive('endlevel', function () {
            return "<?php endif; // app('laratrust')->hasLevelOrGreater or hasLevelOrLess or hasLevelBetween ?>";
        });
    }
}
