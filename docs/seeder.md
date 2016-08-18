# Seeder

Laratrust comes with a database seeder, this seeder helps you filling the permissions for each role depending on the module, and creates one user for each role.

If you haven't run `php artisan vendor:publish` you should run it in order to customize the roles, modules and permissions in each case.

After you run `php artisan vendor:publish`, you will have a `config/laratrust_seeder.php` file and it looks like this:

```php
return [
    'role_structure' => [
        'superadministrator' => [
            'users' => 'c,r,u,d',
            'acl' => 'c,r,u,d',
            'profile' => 'r,u'
        ],
        'administrator' => [
            'users' => 'c,r,u,d',
            'profile' => 'r,u'
        ],
        'user' => [
            'profile' => 'r,u'
        ],
    ],
    ...
];
```

To understand the file you must know:
- The first level is the roles.
- The second level is the modules.
- The second level assignments are the permissions.

With that in mind, you should arrange your roles, modules and permissions like this:

```php
return [
    'role' => [
        'module' => 'permissions',
    ],
];
```
### Permissions
In case that you don't want to use the `c,r,u,d` permissions, in the `config/laratrust_seeder.php` there the `permissions_map` where you can change the permissions mapping.