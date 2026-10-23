---
sidebarDepth: 2
---

# Events

Laratrust dispatches an event each time roles or permissions are added, removed or synced. The events are classes in the `Laratrust\Events` namespace, and you listen to them like to any other [Laravel event](https://laravel.com/docs/events).

| Event               | Dispatched when                                      |
| :------------------ | :--------------------------------------------------- |
| `RoleAdded`         | A role is added to a user.                           |
| `RoleRemoved`       | A role is removed from a user.                       |
| `RoleSynced`        | The roles of a user are synced.                      |
| `PermissionAdded`   | A permission is given to a user or a role.           |
| `PermissionRemoved` | A permission is removed from a user or a role.       |
| `PermissionSynced`  | The permissions of a user or a role are synced.      |

::: tip NOTE
`addRoles()`, `givePermissions()` and the other methods that take many items dispatch one event for each item. `removeRoles()` and `removePermissions()` without arguments sync to an empty list, so they dispatch a single `RoleSynced` or `PermissionSynced` event.
:::

## Listening to the events

You can register a closure, e.g. in the `boot` method of your `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Event;
use Laratrust\Events\RoleAdded;

public function boot(): void
{
    Event::listen(function (RoleAdded $event) {
        logger("Role {$event->role} added to user {$event->model->getKey()}");
    });
}
```

Or you can create a listener class. Laravel [discovers](https://laravel.com/docs/events#event-discovery) the listeners in your `app/Listeners` directory by the type of their `handle` method:

```php
<?php

namespace App\Listeners;

use Laratrust\Events\PermissionSynced;

class LogPermissionChanges
{
    public function handle(PermissionSynced $event): void
    {
        // $event->model, $event->changes, $event->team
    }
}
```

The events are dispatched after the changes are saved in the database.

## Event properties

Each event has public, read-only properties:

- `$model`: The user. For the permission events, it can also be the role.
- `$role` (`RoleAdded`, `RoleRemoved`): The key of the role.
- `$permission` (`PermissionAdded`, `PermissionRemoved`): The key of the permission.
- `$changes` (`RoleSynced`, `PermissionSynced`): The array returned by Eloquent's `sync` method, with the `attached`, `detached` and `updated` keys.
- `$team`: The key of the team. It is `null` when no team was given, when the teams feature is disabled, and for the permission events of a role.

To tell a user's permission event from a role's one, check the model:

```php
use Laratrust\Contracts\Role;
use Laratrust\Events\PermissionAdded;

Event::listen(function (PermissionAdded $event) {
    if ($event->model instanceof Role) {
        // A permission was given to a role.
    }
});
```
