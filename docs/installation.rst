Installation
============

1. In order to install Laratrust in your Laravel project, just run the `composer require` command from your terminal::

        composer require "santigarcor/laratrust:4.0.*"

.. NOTE::
    If you are using Laravel 5.5 you don't need to do steps 2 and 3.

2. Then in your ``config/app.php`` add the following to the providers array::

    Laratrust\LaratrustServiceProvider::class,

3. In the same ``config/app.php`` add the following to the ``aliases`` array::

    'Laratrust'   => Laratrust\LaratrustFacade::class,

4. Run the next command to publish all the configuration files::
    
    php artisan vendor:publish --tag="laratrust"
    
.. WARNING::
    If this command did not publish any files, chances are, the Laratrust service provider hasn't been registered. Try clearing your configuration cache::

        php artisan config:clear

5. If you are going to use :doc:`usage/middleware` you also need to add the following to ``routeMiddleware`` array in ``app/Http/Kernel.php``::

    'role' => \Laratrust\Middleware\LaratrustRole::class,
    'permission' => \Laratrust\Middleware\LaratrustPermission::class,
    'ability' => \Laratrust\Middleware\LaratrustAbility::class,
