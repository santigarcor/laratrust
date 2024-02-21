# Upgrade from 7.x to 8.x

::: tip IMPORTANT
Laratrust 8.x requires Laravel 10.x
:::

In order to upgrade from Laratrust 7.x to 8.x you have to follow these steps:

1. Change your `composer.json` to require the 8.x version of Laratrust:

```json
"santigarcor/laratrust": "^8.0"
```

2. Run `composer update` to update the source code.

3. Run `php artisan config:clear`, `php artisan cache:clear` and `php artisan view:clear`.

4. Update your `config/laratrust.php`:

   4.1. Backup your `config/laratrust.php` configuration values.

   4.2. Delete the `config/laratrust.php` file.

   4.3. Run `php artisan vendor:publish --tag=laratrust`.

   4.4. Update the `config/laratrust.php` file with your old values.

5. The ownership feature has been completely removed. Then migrate that code.

6. The methods `isA` and `isAn` have been removed, replace with `hasRole`.

7. The user model now must implement the `Laratrust\Contracts\LaratrustUser` interface and use the `Laratrust\Traits\HasRolesAndPermissions` trait.

8. The `Role`, `Permission` and `Team` models should inherit now from the following classes:

| Old                                    | New                           |
| :------------------------------------- | :---------------------------- |
| `Laratrust\Models\LaratrustRole`       | `Laratrust\Models\Role`       |
| `Laratrust\Models\LaratrustPermission` | `Laratrust\Models\Permission` |
| `Laratrust\Models\LaratrustTeam`       | `Laratrust\Models\Team`       |

9. The methods signatures have been changed. Follow this table to migrate them.

| Old                | New                |
| :----------------- | :----------------- |
| `attachPermission` | `givePermission`   |
| `attachPermissions`| `givePermissions`  |
| `detachPermission` | `removePermission` |
| `detachPermissions`| `removePermissions`|
| `attachRole`       | `addRole`          |
| `detachRole`       | `removeRole`       |

10. The methods signatures for the events have been changed. Follow this table to migrate them.

| Old                  | New                 |
| :------------------- | :------------------ |
| `roleAttached`       | `roleAdded`         |
| `roleDetached`       | `roleRemoved`       |
| `permissionAttached` | `permissionAdded`   |
| `permissionDetached` | `permissionRemoved` |

11. The Querying relation signatures have been changed. Follow this table to migrate them.

| Old                  | New                    |
| :------------------- | :--------------------- |
| `whereRoleIs`        | `whereHasRole`         |
| `orWhereRoleIs`      | `orWhereHasRole`       |
| `wherePermissionIs`  | `whereHasPermission`   |
| `orWherePermissionIs`| `orWhereHasPermission` |
| `whereDoesntHaveRole`| `whereDoesntHaveRoles` |
    

Now you can use the 8.x version without any problem.
