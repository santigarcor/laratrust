---
sidebarDepth: 2
---

# Querying Relations

The `LaratrustUserTrait` has the `roles` and `permissions` relationship, that return a `MorphToMany` relationships.

The `roles` relationship has all the roles attached to the user.

The `permissions` relationship has all the direct permissions attached to the user.

## All Permissions

If you want to retrieve all the user permissions, you can use the `allPermissions` method. It returns a unified collection with all the permissions related to the user (via the roles and permissions relationships).

```php
dump($user->allPermissions());
/*
    Illuminate\Database\Eloquent\Collection {#646
    #items: array:2 [
    0 => App\Permission {#662
        ...
        #attributes: array:6 [
        "id" => "1"
        "name" => "edit-users"
        "display_name" => "Edit Users"
        "description" => null
        "created_at" => "2017-06-19 04:58:30"
        "updated_at" => "2017-06-19 04:58:30"
        ]
        ...
    }
    1 => App\Permission {#667
        ...
        #attributes: array:6 [
        "id" => "2"
        "name" => "manage-users"
        "display_name" => "Manage Users"
        "description" => null
        "created_at" => "2017-06-19 04:58:30"
        "updated_at" => "2017-06-19 04:58:30"
        ]
        ...
    }
    ]
}
*/
```

## By Role
To retrieve the users that have some role you can use the query scope `whereRoleIs` or `orWhereRoleIs`:

```php
// This will return the users with 'admin' or 'regular-user' role.
$users = User::whereRoleIs('admin')->orWhereRoleIs('regular-user')->get();
```

To get all the users with a set of roles, you can pass an array to the scope:

```php
// This acts as a whereIn check in the database.
$users = User::whereRoleIs(['admin', 'regular-user'])->get();
```

## By Permissions

To retrieve the users that have some permission you can use the query scope `wherePermissionIs` or `orWherePermissionIs`:

```php
// This will return the users with 'edit-user' or 'create-user' permission.
$users = User::wherePermissionIs('edit-user')->orWherePermissionIs('create-user')->get();
```

To get all the users with a set of permissions, you can pass an array to the scope:
```php
// This acts as a whereIn check in the database.
$users = User::wherePermissionIs(['edit-user', 'create-user'])->get();
```

## Roles & Permissions Absence

To retrive all the users that don't have any roles or permissions you can use:

```php
User::whereDoesntHaveRole()->get();

User::whereDoesntHavePermission()->get();
```