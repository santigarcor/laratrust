# Seeder

Laratrust comes with a database seeder, this seeder helps you fill the permissions for each role depending on the module. It can also create one user for each role, when the `create_users` option is `true`, with the `password` password.

::: tip NOTE
- The seeder is going to work with the first user model inside the `user_models` array.

- The seeder doesn't support teams.
:::

::: warning
The seeder empties the `role_user`, `permission_user` and `permission_role` tables before it runs. With the `truncate_tables` option (enabled by default), it also empties the `roles` and `permissions` tables, and the users table when `create_users` is `true`. Don't run it on a database with data you want to keep.
:::

To generate the seeder you have to run:

```bash
php artisan laratrust:seeder
```

It creates the `database/seeders/LaratrustSeeder.php` file. If the file already exists, the command doesn't change it.

Then to customize the roles, modules and permissions you can publish the `laratrust_seeder.php` file:

```bash
php artisan vendor:publish --tag="laratrust-seeder"
```

In the `database/seeders/DatabaseSeeder.php` file you have to add this to the `run` method:

```php
$this->call(LaratrustSeeder::class);
```

## Seeder configuration file
Your `config/laratrust_seeder.php` file looks like this by default:

```php
return [
    ...
    'roles_structure' => [
        'superadministrator' => [
            'users' => 'c,r,u,d',
            'payments' => 'c,r,u,d',
            'profile' => 'r,u'
        ],
        'administrator' => [
            'users' => 'c,r,u,d',
            'profile' => 'r,u'
        ],
        'user' => [
            'profile' => 'r,u',
        ],
        'role_name' => [
            'module_1_name' => 'c,r,u,d',
        ]
    ],
    'permissions_map' => [
        'c' => 'create',
        'r' => 'read',
        'u' => 'update',
        'd' => 'delete'
    ],
    ...
];

```

To understand the `roles_structure` you must know:

* The first level represents the roles.
* The second level represents the modules.
* The second level assignments are the permissions.

With that in mind, you should arrange your roles, modules and permissions like this:

```php
return [
    'roles_structure' => [
        'role' => [
            'module' => 'permissions',
        ],
    ]
];
```

## Permissions

In case that you do not want to use the `c,r,u,d` permissions, you should change the `permissions_map`.

For example:
```php
return [
    ...
    'roles_structure' => [
        'role_name' => [
            'module_1_name' => 'a,s,e,d',
        ]
    ],
    'permissions_map' => [
        'a' => 'add',
        's' => 'show',
        'e' => 'edit',
        'd' => 'destroy'
    ],
    ...
];

```
