---
sidebarDepth: 2
---

# Querying Relations

The `HasRolesAndPermissions` trait has the `roles` and `permissions` relationships, which return `MorphToMany` relationships.

The `roles` relationship has all the roles added to the user.

The `permissions` relationship has all the direct permissions given to the user.

## All Permissions

If you want to retrieve all the user permissions, you can use the `allPermissions` method. It returns a unified collection with all the permissions related to the user (via the roles and permissions relationships).

You can pass the columns to select, and the team. With a team, only the roles added and the permissions given within that team are used; `null` means without a team:

```php
$user->allPermissions(['name', 'display_name']);
$user->allPermissions(team: 'my-awesome-team');
```

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

To retrieve the users that have some role you can use the query scope `whereHasRole` or `orWhereHasRole`:

```php
// This will return the users with 'admin' or 'regular-user' role.
$users = User::whereHasRole('admin')->orWhereHasRole('regular-user')->get();
```

To get all the users with a set of roles, you can pass an array to the scope:

```php
// This acts as a whereIn check in the database.
$users = User::whereHasRole(['admin', 'regular-user'])->get();

// Like in the checks, pipes separate the names.
$users = User::whereHasRole('admin|regular-user')->get();
```

::: tip NOTE
The scopes don't support wildcards: `whereHasPermission('admin.*')` looks for a permission named `admin.*`.
:::

With the teams feature, you can pass the team as the second argument. The team is the one the role was added within:

```php
$users = User::whereHasRole('admin', 'my-awesome-team')->get();
```

## By Permissions

To retrieve the users that have some permission you can use the query scope `whereHasPermission` or `orWhereHasPermission`:

```php
// This will return the users with 'edit-user' or 'create-user' permission.
$users = User::whereHasPermission('edit-user')->orWhereHasPermission('create-user')->get();
```

To get all the users with a set of permissions, you can pass an array to the scope:

```php
// This acts as a whereIn check in the database.
$users = User::whereHasPermission(['edit-user', 'create-user'])->get();
```

The scope finds the permissions given directly and through the roles. With the teams feature, you can pass the team as the second argument:

```php
$users = User::whereHasPermission('edit-user', 'my-awesome-team')->get();
```

::: tip NOTE
The scopes follow the `teams.strict_check` value. When it is `false`, a `null` team means any team. When it is `true`, it means without a team.
:::

## Roles & Permissions Absence

To retrieve all the users that don't have any roles or permissions you can use:

```php
// The users without roles.
User::whereDoesntHaveRoles()->get();

// The users without permissions: neither given directly nor through their roles.
User::whereDoesntHavePermissions()->get();
```
