# Troubleshooting

If you encounter an error when doing the migration that looks like:

```
SQLSTATE[HY000]: General error: 1005 Can't create table 'laravelbootstrapstarter.#sql-42c_f8' (errno: 150)
    (SQL: alter table `role_user` add constraint role_user_user_id_foreign foreign key (`user_id`)
    references `users` (`id`)) (Bindings: array ())
```

Then it's likely that the `id` column in your user table does not match the `user_id` column in `role_user`.
Make sure both are `INT(10)`.

---

When trying to use the LaratrustUserTrait methods, you encounter the error which looks like

    Class name must be a valid object or a string

then probably you don't have published Laratrust assets or something went wrong when you did it.
First of all check that you have the `laratrust.php` file in your `app/config` directory.
If you don't, then try `php artisan vendor:publish` and, if it does not appear, manually copy the `/vendor/santigarcor/laratrust/src/config/config.php` file in your config directory and rename it `laratrust.php`.

---

If you are using SoftDeletes and get this error:

    Trait method restore has not been applied, because there are collisions with other trait methods on App\User

Please add the following to your respective Model (User, Role):

```php
    use LaratrustModelTrait {
        LaratrustModelTrait::restore insteadof SoftDeletes;
    }
```

But remember to change the `LaratrustModelTrait` for `LaratrustUserTrait` or `LaratrustRoleTrait`