# Upgrade from 6.x to 7.x

::: tip IMPORTANT
Laratrust 7.x requires Laravel 9.x
:::

In order to upgrade from Laratrust 6.x to 7.x you have to follow these steps:

1. Change your `composer.json` to require the 7.x version of Laratrust:
```json
"santigarcor/laratrust": "^7.0"
```

2. Run `composer update` to update the source code.

3. Run `php artisan config:clear` and `php artisan cache:clear`.

4. Update your `config/laratrust.php`:

    4.1. Backup your `config/laratrust.php` configuration values.

    4.2. Delete the `config/laratrust.php` file.

    4.3. Run `php artisan vendor:publish --tag=laratrust`.

    4.4. Update the `config/laratrust.php` file with your old values.

Now you can use the 7.x version without any problem.
