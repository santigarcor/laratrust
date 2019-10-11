# After Installation

## Configuration Files

In your `config/laratrust.php` file you will find all the package configurations that you can customize.

## Teams Feature

If you want to use the teams feature that allows you to attach roles and permissions to an user depending on a team, you must change the `use_teams` key value to `true` in your `config/laratrust.php` file. Then follow the <docs-link to="/configuration/teams.html">teams configuration</docs-link> guide.

## Multiple User Models

In the `config/laratrust.php` file you will find an `user_models` array, it contains the information about the multiple user models and the name of the relationships inside the `Role` and `Permission` models. For example:

```php
'user_models' => [
    'users' => 'App\User',
],
```

::: tip NOTE
The value of the `key` in the `key => value` pair defines the name of the relationship inside the `Role` and `Permission` models.
:::

It means that there is only one user model using Laratrust, and the relationship with the `Role` and `Permission` models is going to be called like this:

```php
$role->users;
$role->users();
```

::: tip NOTE
Inside the `role_user` and `permission_user` tables the `user_type` column will be set with the user's fully qualified class name, as the [polymorphic](https://laravel.com/docs/eloquent-relationships#polymorphic-relations) relations describe it in Laravel docs.

If you want to use the MorphMap feature just change the `use_morph_map` value to `true` in Laratrust's configuration file.
:::

## Automatic setup (Recommended)

If you want to let laratrust to setup by itself, just run the following command

```bash
php artisan laratrust:setup
```

::: warning
If Laravel does not recognize this command, the Laratrust service provider hasn't been registered. Check `providers` array in `config/app.php` and try clearing your configuration cache
```bash
php artisan config:clear
```
:::

This command will generate the migrations, create the `Role` and `Permission` models (if you are using the teams feature it will also create a `Team` model) and will add the trait to the configured user models.

::: tip NOTE
The user trait will be added to the models configured in the `config/laratrust.php` file.
:::

And then do not forget to run
```bash
composer dump-autoload
```

::: tip IMPORTANT
**If you did the steps above you are done with the configuration, if not, please read and follow the whole configuration process**
:::
