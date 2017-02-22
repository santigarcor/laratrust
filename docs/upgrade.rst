Upgrade from 3.1 to 3.2
=======================

In order to upgrade from Laratrust 3.1 to 3.2 you have to follow these steps:

1. Change your ``composer.json`` to require the 3.2 version of laratrust::
    
    "santigarcor/laratrust": "3.2.*"

2. Run ``composer update`` to update the source code.

3. Add in your ``config/laratrust.php`` file this block:

   .. code-block:: php

        'user_models' => [
            'users' => 'App\User',
        ],

   And configure it with you user models information according to the new :ref:`multiple-user-models` explanation.

4. Run ``php artisan laratrust:add-trait`` to add the ``LaratrustUserTrait`` to the user models.

5. Run ``php artisan laratrust:upgrade`` to create the migration with the database upgrade.

6. Run ``php artisan migrate`` to apply the migration created in the previous step.

7. Delete the ``LaratrustSeeder.php`` file and run ``php artisan laratrust:seeder``.

8. Run ``composer dump-autoload``.

Now you can use the 3.2 version without any problem.