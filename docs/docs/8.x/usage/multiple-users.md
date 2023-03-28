# Multiple User Models

Laratrust supports adding roles/permissions to multiple user models.

In the `config/laratrust.php` file you will find an `user_models` array, it contains the information about the multiple user models and the name of the relationships inside the `Role` and `Permission` models. For example:

```php
'user_models' => [
    'users' => \App\Model\User::class,
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
