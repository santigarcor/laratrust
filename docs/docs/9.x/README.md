---
meta:
  - name: keywords
    content: laravel roles permissions package teams panel role permission
---

# Introduction

Laratrust is a package that lets you add roles and permissions inside your Laravel application. All of this through a very simple configuration process and API.

Here you can see some examples:

```php
$adminRole = Role::where('name', 'admin')->first();
$editUserPermission = Permission::where('name', 'edit-user')->first();
$user = User::find(1);

$user->addRole($adminRole);
// Or
$user->addRole('admin');

$user->givePermission($editUserPermission);
// Or
$user->givePermission('edit-user');
```

You can also check if a user has some permissions or roles:

```php
$user->isAbleTo('edit-user');
$user->hasPermission('edit-user');

$user->hasRole('admin');
```

It also supports teams, multiple users and it has a simple admin panel and it is compatible with Laravel's policies and gates system.
