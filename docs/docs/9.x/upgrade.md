---
sidebarDepth: 2
---

# Upgrade from 8.x to 9.x

::: tip IMPORTANT
Laratrust 9.x requires **Laravel 13** and **PHP 8.3** or higher. If you can't upgrade yet, stay on 8.x, which supports Laravel 10 to 13.
:::

Most applications only need the [required steps](#required-steps). The other sections list the API and behavior changes. Search your code for the methods they name.

## Required steps

1. Change your `composer.json` to require the 9.x version of Laratrust:

```json
"santigarcor/laratrust": "^9.0"
```

2. Run `composer update santigarcor/laratrust`.

3. Update your `config/laratrust.php`. The `checkers` section is gone and the cache comments changed:

   3.1. Back up your `config/laratrust.php` values.

   3.2. Delete the `config/laratrust.php` file.

   3.3. Run `php artisan vendor:publish --tag=laratrust`.

   3.4. Put your old values back in the new file.

4. Run `php artisan config:clear`, `php artisan cache:clear` and `php artisan view:clear`. The cache keys changed, so the old cached roles and permissions are never read again.

5. If you used the admin panel, delete the `public/vendor/laratrust` directory. The panel doesn't publish its assets anymore.

6. If you published the panel views (`resources/views/vendor/laratrust/panel`), delete them. They use classes and assets that don't exist in 9.x. Publish them again only if you need to customize them.

## Checkers

The `checkers` configuration and the `query` checkers are removed. Laratrust has a single checker, and you can't replace it with a custom one anymore.

The checks are also faster:

- The roles and permissions of a user load with at most 4 queries, however many roles the user has. 8.x used one query per role.
- The result is memoized for the current request or queue job, even when the cache is disabled. A repeated `hasPermission()` call doesn't query the database or the cache again.
- Exact permission names are a hash lookup. Only the names with a `*` wildcard compare against each permission.

If you used a custom checker, move that logic to a [Gate](https://laravel.com/docs/authorization#gates) or a policy.

## Cache

- The cache keys are now `laratrust_user_{user_models key}_{id}` for the users and `laratrust_role_{id}` for the roles. To change them, e.g. per tenant, override the new `laratrustCacheKey()` method on your user and role models. Refer to <docs-link to="/usage/roles-and-permissions.html#cache">the cache docs</docs-link>.
- Laratrust flushes the cache when you use its methods (`addRole`, `givePermission`, `syncRoles`, …) and when a role is saved or deleted. If you change the pivot tables directly, e.g. `$user->roles()->attach($id)`, call `$user->flushLaratrustCache()` or `$role->flushLaratrustCache()` after the change. Because of the per-request memoization, this is now necessary also when the cache is disabled.
- The deprecated `flushCache()` method is removed from the user trait and the `Role` model. Use `flushLaratrustCache()`.

## Events

The string events, the static listeners and the `laratrustObserve()` observers are replaced by event classes in the `Laratrust\Events` namespace. Laratrust dispatches them with Laravel's event dispatcher, so you listen to them like to any other Laravel event.

| 8.x                                           | 9.x                                |
| :-------------------------------------------- | :--------------------------------- |
| `roleAdded($user, $role, $team)`              | `RoleAdded($model, $role, $team)`               |
| `roleRemoved($user, $role, $team)`            | `RoleRemoved($model, $role, $team)`             |
| `roleSynced($user, $changes, $team)`          | `RoleSynced($model, $changes, $team)`           |
| `permissionAdded($model, $permission, $team)` | `PermissionAdded($model, $permission, $team)`   |
| `permissionRemoved($model, $permission, $team)` | `PermissionRemoved($model, $permission, $team)` |
| `permissionSynced($model, $changes, $team)`   | `PermissionSynced($model, $changes, $team)`     |

Each event has public, read-only properties with these names. `$model` is the user, or the role for the permission events of a role. `$team` is always the team key (or `null`), never a model.

For example, this 8.x code:

```php
User::roleAdded(function ($user, $role, $team) {
    // ...
});
```

becomes:

```php
use Illuminate\Support\Facades\Event;
use Laratrust\Events\RoleAdded;

Event::listen(function (RoleAdded $event) {
    // $event->model, $event->role, $event->team
});
```

These methods are removed: `laratrustObserve()`, `laratrustFlushObservables()`, `registerLaratrustEvent()` and the static `roleAdded()`, `roleRemoved()`, `roleSynced()`, `permissionAdded()`, `permissionRemoved()` and `permissionSynced()`. Move the methods of your observers to [listeners](https://laravel.com/docs/events#defining-listeners).

## Query scopes

- The scopes are now methods with Laravel's `#[Scope]` attribute. You call them the same way, e.g. `User::whereHasRole('admin')`. If your user model overrides one of them, e.g. `scopeWhereHasRole()`, Laravel ignores your override. Change it to a `protected` method with the attribute:

```php
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

#[Scope]
protected function whereHasRole(Builder $query, $role = '', $team = null, string $boolean = 'and'): Builder
{
    // ...
}
```

- `whereDoesntHavePermissions()` now returns only the users that have no permissions at all: no direct permissions **and** no permissions through their roles. In 8.x it returned the users that had no direct permissions **or** no permissions through their roles.
- `whereHasRole()` and `whereHasPermission()` apply the team to the pivot table, so the queries are no longer ambiguous when the tables have the same column names ([#695](https://github.com/santigarcor/laratrust/issues/695)). `whereHasPermission()` now matches a permission through a role only if the role was added in that team. When teams are disabled, the scopes ignore the team.
- `allPermissions($columns, $team)` now applies the team to the `role_user` and `permission_user` pivots. In 8.x it filtered the permissions table, which has no team column.

## Middleware

- The parameters are always in this order: the names, then the team (optional), then the options. If a parameter comes in the wrong place, e.g. a team after `require_all`, the middleware now throws an `InvalidArgumentException`. In 8.x it ignored the team, so the check ran without it.
- To check more than one guard, separate them with pipes: `guard:web|admin`. The middleware checks the user of the first authenticated guard, like Laravel's `auth` middleware. Refer to <docs-link to="/usage/middleware.html#using-different-guards">the middleware docs</docs-link>. The 8.x syntax (`require_all|guard:api`) still works.
- The abort and redirect messages are passed through `__()`, so they can be translation keys.
- New `using()` helpers build the middleware string for you, e.g. `Role::using('admin', team: 'my-team')`.

## Models

- The `Role`, `Permission` and `Team` models now respect a `$table` property set in your models. If you don't set one, they use the table from `config/laratrust.php`, as before.
- If a class in `laratrust.models` or `laratrust.user_models` isn't an Eloquent model that implements the Laratrust contract (`Laratrust\Contracts\Role`, `Permission`, `Team` or `LaratrustUser`), Laratrust throws an `InvalidArgumentException` that names the configuration key.
- `$role->hasPermission('a|b')` now splits the pipe-separated names, like the user methods do.
- The `users` relationships on the `Role`, `Permission` and `Team` models are now real Eloquent relationships (named after the keys in `user_models`), so you can eager load them: `Role::with('users')->get()`.

## Blade directives

`@role`, `@permission` and `@ability` are now registered with `Blade::if`. The existing templates work as before, and you can also use `@elserole`, `@unlessrole` and the same forms of the other directives.

## Removed

| Removed                       | Use instead                                                    |
| :---------------------------- | :------------------------------------------------------------- |
| `flushCache()`                | `flushLaratrustCache()`                                        |
| `php artisan laratrust:add-trait` | Add the trait and the interface by hand, refer to <docs-link to="/installation.html">the installation</docs-link>. |
| `php artisan laratrust:upgrade` | Nothing: the database schema didn't change.                  |
| `Laratrust\Helper`            | It was internal. For the panel role restrictions, use `Laratrust\Panel\RoleRestrictions`. |
| The `laratrust-assets` publish tag | Nothing: the panel includes its assets.                   |
| The `checkers` configuration  | Nothing: there is only one checker.                            |

## Seeder

`php artisan laratrust:seeder` now always writes the seeder to `database/seeders/LaratrustSeeder.php`, in the `Database\Seeders` namespace.

## Admin panel

The panel was rebuilt with Tailwind CSS v4 and Alpine.js v3. Its routes and configuration didn't change. Also:

- The CSS and JavaScript are included in the pages, so there is nothing to publish. When you use a [CSP nonce](https://laravel.com/docs/vite#content-security-policy-csp-nonce) with `Vite::useCspNonce()`, the panel adds it to its `<style>` and `<script>` tags.
- The `panel.create_permissions` option is now enforced on the routes: when it is `false`, the create and store routes return a `404`. In 8.x it only hid the button.

Now you can use the 9.x version.
