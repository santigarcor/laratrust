# Events

Laratrust comes with an events system that works like the Laravel [model events](https://laravel.com/docs/eloquent#events). The events that you can listen to are **roleAttached**, **roleDetached**, **permissionAttached**, **permissionDetached**, **roleSynced**, **permissionSynced**.

::: tip NOTE
Inside the Role model only the **permissionAttached**, **permissionDetached** and **permissionSynced** events will be fired.
:::

If you want to listen to a Laratrust event, inside your `User` or `Role` models put this:

```php
<?php

namespace App;

use Laratrust\Traits\LaratrustUserTrait;

class User extends Model
{
    use LaratrustUserTrait;

    public static function boot() {
        parent::boot();

        static::roleAttached(function($user, $role, $team) {
        });
        static::roleSynced(function($user, $changes, $team) {
        });
    }
}
```

::: tip NOTE
The `$team` parameter is optional and if you are not using teams, it will be set to null.
:::

The eventing system also supports observable classes:

```php
<?php

namespace App\Observers;

use App\User;

class UserObserver
{

    public function roleAttached(User $user, $role, $team)
    {
        //
    }

    public function roleSynced(User $user, $changes, $team)
    {
        //
    }
}
```

::: tip IMPORTANT
To register an observer, use the `laratrustObserve` method on the model you wish to observe.
:::

You may register observers in the boot method of one of your service providers. In this example, we'll register the observer in the AppServiceProvider:

```php
<?php

namespace App\Providers;

use App\User;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    public function boot()
    {
        User::laratrustObserve(UserObserver::class);
    }

    ...
}
```

::: tip NOTE
- Inside your observable classes you can have your normal model events methods alongside Laratrust's events methods.
- If you want to register Laratrust events and also eloquent events you should call both `observe` and `laratrustObserve` methods.
:::

### Flushing events and observables

If you want to flush the observables and events from laratrust you should add the following in your code:

```php
User::laratrustFlushObservables();
User::flushEventListeners();
```

## Available Events


### User Events

- `roleAttached($user, $role, $team = null)`
    - `$user`: The user to whom the role was attached.
    - `$role`: The role id that was attached to the `$user`.
    - `$team`: The team id that was used to attach the role to the `$user`.

- `roleDetached($user, $role, $team = null)`
    - `$user`: The user to whom the role was detached.
    - `$role`: The role id that was detached from the `$user`.
    - `$team`: The team id that was used to detach the role from the `$user`.

- `permissionAttached($user, $permission, $team = null)`
    - `$user`: The user to whom the permission was attached.
    - `$permission`: The permission id that was attached to the `$user`.
    - `$team`: The team id that was used to attach the permission to the `$user`.

- `permissionDetached($user, $permission, $team = null)`
    - `$user`: The user to whom the permission was detached.
    - `$permission`: The permission id that was detached from the `$user`.
    - `$team`: The team id that was used to detach the permission from the `$user`.

- `roleSynced($user, $changes, $team)`
    - `$user`: The user to whom the roles were synced.
    - `$changes`: The value returned by the eloquent `sync` method containing the changes made in the database.
    - `$team`: The team id that was used to sync the roles to the user.

- `permissionSynced()`
    - `$user`: The user to whom the permissions were synced.
    - `$changes`: The value returned by the eloquent `sync` method containing the changes made in the database.
    - `$team`: The team id that was used to sync the permissions to the user.

### Role Events

- `permissionAttached($role, $permission)`
    - `$role`: The role to whom the permission was attached.
    - `$permission`: The permission id that was attached to the `$role`.

- `permissionDetached($role, $permission)`
    - `$role`: The role to whom the permission was detached.
    - `$permission`: The permission id that was detached from the `$role`.

- `permissionSynced()`
    - `$role`: The role to whom the permissions were synced.
    - `$changes`: The value returned by the eloquent `sync` method containing the changes made in the database.

