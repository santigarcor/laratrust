Upgrade from 3.2 to 4.0
=======================

.. IMPORTANT::
    Laratrust 4.0 requires Laravel >= 5.1.40.

In order to upgrade from Laratrust 3.3 to 4.0 you have to follow these steps:

1. Change your ``composer.json`` to require the 4.0 version of Laratrust::

    "santigarcor/laratrust": "4.0.*"

2. Run ``composer update`` to update the source code.

3. Update your ``config/laratrust.php``:

    3.1. Backup your ``config/laratrust.php`` configuration values.

    3.2. Delete the ``config/laratrust.php`` file.

    3.3. Run ``php artisan vendor:publish --tag=laratrust``.

    3.4. Update the ``config/laratrust.php`` file with your old values.

    .. NOTE::
        Leave the ``use_teams`` key in false during the upgrade process.

4. If you use any values of the ``config/laratrust.php`` in your application code, update those values with the new file structure.

5. If you use the ability middleware and you pass the third argument (require all), please change it like this::

    // From
    'middleware' => ['ability:admin|owner,create-post|edit-user,true']
    // To
    'middleware' => ['ability:admin|owner,create-post|edit-user,require_all']

6. Run ``php artisan laratrust:upgrade`` to create the migration with the database upgrade.

7. Run ``php artisan migrate`` to apply the migration created in the previous step.

8. Delete the ``LaratrustSeeder.php`` file and run ``php artisan laratrust:seeder``.

9. Run ``composer dump-autoload``.

Now you can use the 4.0 version without any problem.