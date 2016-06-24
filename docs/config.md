# After Installation

## Configuration Files
Set the property values in the `config/auth.php`.
These values will be used by laratrust to refer to the correct user model.

You can also publish the configuration for this package to further customize table names and model namespaces.

Just use `php artisan vendor:publish` and a `laratrust.php` file will be created in your app/config directory.

## Automatic setup (Recommended)
If you want to let laratrust to setup by itselft, just run the following command:

```bash
php artisan laratrust:setup
```

This command will generate the migrations, create the `Role` and `Permission` models and will add the trait to the `User` model.

> The user trait will be added to the Model configured in the `auth.php` file.

And then don't forget to run:

```bash
composer dump-autoload
```

###_If you did the steps above you are done with the configuration, if not, please read and follow the whole configuration process_

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
- `role_user` &mdash; stores [many-to-many](https://laravel.com/docs/eloquent-relationships#many-to-many) relations between roles and users
- `permission_role` &mdash; stores [many-to-many](https://laravel.com/docs/eloquent-relationships#many-to-many) relations between roles and permissions