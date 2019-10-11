# Upgrade from 5.1 to 5.2

::: tip IMPORTANT
Laratrust 5.2 requires Laravel >= 5.6 and php >= 7.1.
:::

In order to upgrade from Laratrust 5.1 to 5.2 you have to follow these steps:

1. Change your `composer.json` to require the 5.2 version of Laratrust:
```json
"santigarcor/laratrust": "5.2.*"
```

2. Run `composer update` to update the source code.

3. Run `php artisan config:clear` and `php artisan cache:clear`.

4. Update your `config/laratrust.php` in the cache ttl from 60 to 3600 or the value you had but now put it in seconds.

5. Run `composer dump-autoload`.

Now you can use the 5.2 version without any problem.
