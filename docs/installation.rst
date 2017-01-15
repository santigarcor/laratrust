Installation
============

1. In order to install Laratrust in your Laravel project, just run the `composer require` command from your terminal::

        composer require "santigarcor/laratrust:3.0.*"

2. Then in your ``config/app.php`` add the following to the providers array::
        
    Laratrust\LaratrustServiceProvider::class,

3. In the same ```config/app.php`` and add the following to the ``aliases`` array::

    'Laratrust'   => Laratrust\LaratrustFacade::class,

4. If you are going to use :doc:`usage/middleware` (requires Laravel 5.1 or later) you also need to add the following to ``routeMiddleware`` array in ``app/Http/Kernel.php``::

    'role' => \Laratrust\Middleware\LaratrustRole::class,
    'permission' => \Laratrust\Middleware\LaratrustPermission::class,
    'ability' => \Laratrust\Middleware\LaratrustAbility::class,