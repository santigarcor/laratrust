# Introduction

Laratrust is a Laravel (>=5.2) package that lets you handle very easily everything related with authorization (roles and permissions) inside your application. All of this through a very simple configuration process and API.

You can see here some examples:

```php
$adminRole = Role::where('name', 'admin')->first();
$editUserPermission = Permission::where('name', 'edit-user')->first();
$user = User::find(1);

$user->attachRole($adminRole);
// Or
$user->attachRole('admin');

$user->attachPermission($editUserPermission);
// Or
$user->attachPermission('edit-user');
```

You can also check if an user has some permissions or roles:

```php
$user->can('edit-user');
$user->isAbleTo('edit-user');

$user->hasRole('admin');
$user->isA('guide');
$user->isAn('admin');
```

It also supports teams, multiple users, objects ownerships and it is compatible with Laravel's policies system.