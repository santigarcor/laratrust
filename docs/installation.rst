Installation
============

1. In order to install Laratrust in your Laravel project, just run the `composer require` command from your terminal::

        composer require "santigarcor/laratrust:3.2.*"

2. Then in your ``config/app.php`` add the following to the providers array::
        
    Laratrust\LaratrustServiceProvider::class,

3. In the same ``config/app.php`` add the following to the ``aliases`` array::

    'Laratrust'   => Laratrust\LaratrustFacade::class,

4. Run the next command to publish all the configuration files::
    
    php artisan vendor:publish --tag="laratrust"

5. If you are going to use :doc:`usage/middleware` (requires Laravel 5.1 or later) you also need to add the following to ``routeMiddleware`` array in ``app/Http/Kernel.php``::

    'role' => \Laratrust\Middleware\LaratrustRole::class,
    'permission' => \Laratrust\Middleware\LaratrustPermission::class,
    'ability' => \Laratrust\Middleware\LaratrustAbility::class,

.. NOTE::

    If you want to use the optional hierarchical levels functionality, add one additional line to the ``routeMiddleware`` array.

.. code-block:: php

    'level' => \Laratrust\Middleware\LaratrustLevel::class