After Installation
==================

Configuration Files
^^^^^^^^^^^^^^^^^^^

Set the proper values in the ``config/auth.php``. These values will be used by laratrust to refer to the user model.

You can also publish the configuration for this package to further customize table names and model namespaces.

Use ``php artisan vendor:publish``, the ``laratrust.php`` and ``laratrust_seeder.php`` files will be created in your ``app/config`` directory.

Automatic setup (Recommended)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you want to let laratrust to setup by itselft, just run the following command::

    php artisan laratrust:setup

This command will generate the migrations, create the ``Role`` and ``Permission`` models and will add the trait to the ``User`` model.

.. NOTE::
    The user trait will be added to the Model configured in the ``auth.php`` file.

And then do not forget to run::

    composer dump-autoload

.. IMPORTANT::
    **If you did the steps above you are done with the configuration, if not, please read and follow the whole configuration process**

Migrations
^^^^^^^^^^

Now generate the Laratrust migration::

    php artisan laratrust:migration

It will generate the ``<timestamp>_laratrust_setup_tables.php`` migration.
You may now run it with the artisan migrate command::

    php artisan migrate

After the migration, four new tables will be present:

* ``roles`` — stores role records
* ``permissions`` — stores permission records
* ``role_user`` — stores `many-to-many <https://laravel.com/docs/eloquent-relationships#many-to-many>`_ relations between roles and users
* ``permission_role`` — stores `many-to-many <https://laravel.com/docs/eloquent-relationships#many-to-many>`_ relations between roles and permissions