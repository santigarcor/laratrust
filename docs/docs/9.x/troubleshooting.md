# Troubleshooting

---

If you make changes directly to the Laratrust tables and when you run your code the changes are not reflected, please clear your application cache using:

```bash
php artisan cache:clear
```

Remember that Laratrust caches the roles and permissions, and it memoizes them for the current request even when the cache is disabled. If you change the pivot tables in your code, e.g. with `$user->roles()->attach($id)`, call `$user->flushLaratrustCache()` after the change.

---

If you encounter an error when doing the migration that looks like::

```log
SQLSTATE[HY000]: General error: 1005 Can't create table 'laravelbootstrapstarter.#sql-42c_f8' (errno: 150)
    (SQL: alter table `role_user` add constraint role_user_user_id_foreign foreign key (`user_id`)
    references `users` (`id`)) (Bindings: array ())
```

Then it is likely that the `id` column in your user table does not match the `user_id` column in `role_user`.
Make sure both have the same type, e.g. both `BIGINT UNSIGNED`.

---

If you get an error which looks like:

    The [laratrust.models.role] config must be an Eloquent model class that implements Laratrust\Contracts\Role.

Then the class in that configuration key doesn't exist, or it doesn't implement that contract. Your models get the contracts when they extend the Laratrust models, e.g. `Laratrust\Models\Role`. Check the `models` and `user_models` values in your `config/laratrust.php` file. If you don't have that file, run `php artisan vendor:publish --tag="laratrust"`.
