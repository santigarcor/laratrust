<?php

namespace Laratrust;

/**
 * This class is the one in charge of registering
 * the blade directives making a difference
 * between the version 5.2 and 5.3
 */
class LaratrustRegistersBladeDirectives
{
    protected $bladeCompiler;

    public function __construct($bladeCompiler)
    {
        $this->bladeCompiler = $bladeCompiler;
    }

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
        $this->bladeCompiler->directive('role', function ($expression) {
            return "<?php if (app('laratrust')->hasRole({$expression})) : ?>";
        });

        // Call to Laratrust::can
        $this->bladeCompiler->directive('permission', function ($expression) {
            return "<?php if (app('laratrust')->can({$expression})) : ?>";
        });

        // Call to Laratrust::ability
        $this->bladeCompiler->directive('ability', function ($expression) {
            return "<?php if (app('laratrust')->ability({$expression})) : ?>";
        });
    }

    /**
     * Registers the directives without parenthesis
     * @return void
     */
    protected function registerWithoutParenthesis()
    {
        // Call to Laratrust::hasRole
        $this->bladeCompiler->directive('role', function ($expression) {
            return "<?php if (app('laratrust')->hasRole{$expression}) : ?>";
        });

        // Call to Laratrust::can
        $this->bladeCompiler->directive('permission', function ($expression) {
            return "<?php if (app('laratrust')->can{$expression}) : ?>";
        });

        // Call to Laratrust::ability
        $this->bladeCompiler->directive('ability', function ($expression) {
            return "<?php if (app('laratrust')->ability{$expression}) : ?>";
        });
    }

    /**
     * Registers the closing directives
     * @return void
     */
    protected function registerClosingDirectives()
    {
        $this->bladeCompiler->directive('endrole', function () {
            return "<?php endif; // app('laratrust')->hasRole ?>";
        });

        $this->bladeCompiler->directive('endpermission', function () {
            return "<?php endif; // app('laratrust')->can ?>";
        });

        $this->bladeCompiler->directive('endability', function () {
            return "<?php endif; // app('laratrust')->ability ?>";
        });
    }
}
