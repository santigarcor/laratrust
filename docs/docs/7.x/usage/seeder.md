# Seeder

Laratrust comes with a database seeder, this seeder helps you fill the permissions for each role depending on the module, and creates one user for each role.

::: tip NOTE
- The seeder is going to work with the first user model inside the `user_models` array.

- The seeder doesn't support teams.
:::

To generate the seeder you have to run:

```bash
php artisan laratrust:seeder
```

Then to customize the roles, modules and permissions you can publish the `laratrust_seeder.php` file:

```bash
php artisan vendor:publish --tag="laratrust-seeder"
```

Finally:

```bash
composer dump-autoload
```

In the `database/seeds/DatabaseSeeder.php` file you have to add this to the `run` method:

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

To understand the `role_structure` you must know:

* The first level represents the roles.
* The second level represents the modules.
* The second level assignments are the permissions.

With that in mind, you should arrange your roles, modules and permissions like this:

```php
return [
    'role_structure' => [
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
