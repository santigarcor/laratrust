Upgrade from 3.0 to 3.1
=======================

In order to upgrade from Laratrust 3.0 to 3.1 you have to follow these steps:

1. Change your ``composer.json`` to require the 3.1 version of laratrust::
    
    "santigarcor/laratrust": "3.1.*",

2. Run ``composer update`` to update the source code.

3. Run ``php artisan laratrust:upgrade`` in order to create the migration with the database upgrade.

4. Run ``php artisan migrate`` to run the migration created in the las step.

5. If you use the ``savePermissions`` method in your code, change it to ``syncPermissions``.

Now you can use the 3.1 version without any problem.