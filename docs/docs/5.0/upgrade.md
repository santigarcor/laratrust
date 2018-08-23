# Upgrade from 4.0 to 5.0

::: tip IMPORTANT
Laratrust 5.0 requires Laravel >= 5.2.32.
:::

In order to upgrade from Laratrust 4.0 to 5.0 you have to follow these steps:

1. Change your `composer.json` to require the 5.0 version of Laratrust:
```json
"santigarcor/laratrust": "5.0.*"
```

2. Run `composer update` to update the source code.

3. Run `php artisan config:clear` and `php artisan cache:clear`.

4. Update your `config/laratrust.php`:

    4.1. Backup your `config/laratrust.php` configuration values.

    4.2. Delete the `config/laratrust.php` file.

    4.3. Run `php artisan vendor:publish --tag=laratrust`.

    4.4. Update the `config/laratrust.php` file with your old values.

    4.5. Set the `middleware.register` value to `false`.

    4.6. Set the `teams_strict_check` value to `true` **only** if you are using teams.

5. Inside your `Role`, `Permission` and `Team` models update the `use` statement from:

    - `use Laratrust\LaratrustRole` to `use Laratrust\Models\LaratrustRole`;
    - `use Laratrust\LaratrustPermission` to `use Laratrust\Models\LaratrustPermission`;
    - `use Laratrust\LaratrustTeam` to `use Laratrust\Models\LaratrustTeam`;

6. If you use the ability method and you pass it comma separated roles or permissions, change them to a pipe separated string:

```php
 // From
$user->ability('admin,owner', 'create-post,edit-user');
// To
$user->ability('admin|owner', 'create-post|edit-user');
```

7. If you are using the `Ownable` interface, please update all the classes implementing it:

```php
// From
public function ownerKey() {}
// To
public function ownerKey($owner) {}
```

8. If you use teams and in your code you use the `syncRoles` and `syncPermissions` read the new <docs-link to="/usage/concepts.html#new-sync-behavior">sync method behavior</docs-link>.

9. The `cachedRoles` and `cachedPermissions` methods now return an array when you have the `laratrust.use_cache` option set to `true`. So if you use these methods, please check your code.

10. Delete the `LaratrustSeeder.php` file and run `php artisan laratrust:seeder`.

11. Run `composer dump-autoload`.

Now you can use the 5.0 version without any problem.
