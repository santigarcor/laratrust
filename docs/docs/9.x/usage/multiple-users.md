# Multiple User Models

Laratrust supports adding roles/permissions to multiple user models.

In the `config/laratrust.php` file you will find an `user_models` array, it contains the information about the multiple user models and the name of the relationships inside the `Role` and `Permission` models. For example:

```php
'user_models' => [
    'users' => \App\Models\User::class,
    'admins' => \App\Models\Admin::class,
],
```

::: tip NOTE
The value of the `key` in the `key => value` pair defines the name of the relationship inside the `Role` and `Permission` models.
:::

It means that there are two user models using Laratrust, and the relationships in the `Role`, `Permission` and `Team` models are going to be called like this:

```php
$role->users;
$role->admins();
```

They are Eloquent relationships, so you can also eager load and query them:

```php
Role::with('users')->get();
Permission::whereHas('admins', fn ($query) => $query->where('active', true))->get();
```

Each user model must implement the `Laratrust\Contracts\LaratrustUser` interface and use the `Laratrust\Traits\HasRolesAndPermissions` trait.

::: tip NOTE
Inside the `role_user` and `permission_user` tables the `user_type` column will be set with the user's fully qualified class name, as the [polymorphic](https://laravel.com/docs/eloquent-relationships#polymorphic-relations) relations describe it in Laravel docs.

If you want to use the MorphMap feature just change the `use_morph_map` value to `true` in Laratrust's configuration file. The keys of the `user_models` array are then stored in the `user_type` column.
:::
