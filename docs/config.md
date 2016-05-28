# After Installation

## Configuration Files
Set the property values in the `config/auth.php`. And in the `users` provider add the users table name.
These values will be used by laratrust to refer to the correct user table and model.

You can also publish the configuration for this package to further customize table names and model namespaces.

Just use `php artisan vendor:publish` and a `laratrust.php` file will be created in your app/config directory.

## Migrations

Now generate the Laratrust migration:

```bash
php artisan laratrust:migration
```

It will generate the `<timestamp>_laratrust_setup_tables.php` migration.
You may now run it with the artisan migrate command:

```bash
php artisan migrate
```

After the migration, four new tables will be present:
- `roles` &mdash; stores role records
- `permissions` &mdash; stores permission records
- `role_user` &mdash; stores [many-to-many](http://laravel.com/docs/4.2/eloquent#many-to-many) relations between roles and users
- `permission_role` &mdash; stores [many-to-many](http://laravel.com/docs/4.2/eloquent#many-to-many) relations between roles and permissions