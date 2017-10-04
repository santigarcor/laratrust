Installation
============

1. In order to install Laratrust in your Laravel project, just run the `composer require` command from your terminal::

        composer require "santigarcor/laratrust:5.0.*"

.. NOTE::
    If you are using Laravel 5.5 you don't need to do steps 2 and 3.

2. Then in your ``config/app.php`` add the following to the providers array::

    Laratrust\LaratrustServiceProvider::class,

3. In the same ``config/app.php`` add the following to the ``aliases`` array::

    'Laratrust'   => Laratrust\LaratrustFacade::class,

4. Publish all the configuration files::

    php artisan vendor:publish --tag="laratrust"

.. WARNING::
    If this command did not publish any files, chances are, the Laratrust service provider hasn't been registered. Try clearing your configuration cache::

        php artisan config:clear

5. The :doc:`middleware </usage/middleware>` are registered automatically as ``role``, ``permission`` and ``ability`` . If you want to customize or change them, please refer to the :ref:`middleware configuration <middleware-configuration>`.


