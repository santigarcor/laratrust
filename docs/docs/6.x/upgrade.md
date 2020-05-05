# Upgrade from 5.2 to 6.X

::: tip IMPORTANT
Laratrust 6.x requires Laravel >= 6.x and php >= 7.1.
:::

In order to upgrade from Laratrust 5.2 to 6.x you have to follow these steps:

1. Change your `composer.json` to require the 6.x version of Laratrust:
```json
"santigarcor/laratrust": "^6.0"
```

2. Run `composer update` to update the source code.

3. Run `php artisan config:clear` and `php artisan cache:clear`.

4. Update your `config/laratrust.php`:

    4.1. Backup your `config/laratrust.php` configuration values.

    4.2. Delete the `config/laratrust.php` file.

    4.3. Run `php artisan vendor:publish --tag=laratrust`.

    4.4. Update the `config/laratrust.php` file with your old values.

5. Delete the `LaratrustSeeder.php` file and run `php artisan laratrust:seeder`.

6. Run `composer dump-autoload`.

7. If you use the `can` method you **MUST** change it to `isAbleTo`. We removed the can method in order to support Laravel policies and gates out of the box.

8. *(Optional)* If you want to use the administration panel provided in the 6.x version, please read <docs-link to="/usage/admin-panel.html">here</docs-link>

Now you can use the 6.x version without any problem.
