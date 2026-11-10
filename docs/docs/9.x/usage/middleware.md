---
sidebarDepth: 2
---

# Middleware

## Configuration

The middleware are registered automatically as `role`, `permission` and `ability`. If you want to change or customize them, go to your `config/laratrust.php`, set the `middleware.register` value to `false` and register the aliases in your `bootstrap/app.php` file:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => \Laratrust\Middleware\Role::class,
        'permission' => \Laratrust\Middleware\Permission::class,
        'ability' => \Laratrust\Middleware\Ability::class,
    ]);
})
```

## Concepts

You can use a middleware to filter routes and route groups by permission, role or ability:

```php
Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::get('/', [AdminController::class, 'welcome']);
    Route::get('/manage', [AdminController::class, 'manageAdmins'])->middleware('permission:manage-admins');
});
```

The middleware parameters are separated by commas, and the values within a parameter by pipes. They always come in this order:

1. The role or permission names. The `ability` middleware takes the roles, then the permissions.
2. The team (optional, only with the teams feature).
3. The options (optional): `require_all` and `guard:{name}`.

If you use the pipe symbol between the names it will be an _OR_ operation:

```php
->middleware('role:admin|root')
// $user->hasRole(['admin', 'root']);

->middleware('permission:edit-post|edit-user')
// $user->hasPermission(['edit-post', 'edit-user']);
```

To emulate _AND_ functionality you can do:

```php
->middleware('role:owner|writer,require_all')
// $user->hasRole(['owner', 'writer'], requireAll: true);

->middleware('permission:edit-post|edit-user,require_all')
// $user->hasPermission(['edit-post', 'edit-user'], requireAll: true);
```

For more complex situations use the `ability` middleware, which accepts the roles, the permissions and the options:

```php
->middleware('ability:admin|owner,create-post|edit-user,require_all')
// $user->ability(['admin', 'owner'], ['create-post', 'edit-user'], options: ['validate_all' => true]);
```

::: warning
If a parameter is in the wrong place, e.g. a team after `require_all`, the middleware throws an `InvalidArgumentException`, so a mistyped route never runs a check without its team.
:::

### Using Different Guards

By default the middleware checks the user of your default guard. To use other guards, add the `guard` option and separate the guards with pipes:

```php
->middleware('role:owner|writer,require_all,guard:api')
->middleware('permission:edit-post|edit-user,guard:web|admin')
->middleware('ability:admin|owner,create-post|edit-user,require_all,guard:web')
```

With many guards, the middleware checks the user of the first authenticated guard, in the given order, like Laravel's `auth` middleware does. If none of them is authenticated, the check fails.

::: tip NOTE
The 8.x syntax, with the options separated by pipes (`require_all|guard:api`), still works.
:::

## Teams

If you are using the teams feature and want to use the middleware checking for your teams, add the team after the names:

```php
->middleware('role:admin|root,my-awesome-team,require_all')
// $user->hasRole(['admin', 'root'], 'my-awesome-team', true);

->middleware('permission:edit-post|edit-user,my-awesome-team,require_all')
// $user->hasPermission(['edit-post', 'edit-user'], 'my-awesome-team', true);

->middleware('ability:admin|owner,create-post|edit-user,my-awesome-team,require_all,guard:web|admin')
// $user->ability(['admin', 'owner'], ['create-post', 'edit-user'], 'my-awesome-team', ['validate_all' => true]);
```

::: tip NOTE
The team and the `require_all` and `guard` options are optional.
:::

A team that doesn't exist matches nothing, so the request is handled as unauthorized.

## Building the middleware string

Instead of writing the strings, you can build them with the `using()` method of each middleware. Its arguments are the same as the checks:

```php
use Laratrust\Middleware\Ability;
use Laratrust\Middleware\Permission;
use Laratrust\Middleware\Role;

Route::get('/posts', [PostController::class, 'index'])
    ->middleware(Role::using(['admin', 'editor'], team: 'acme', requireAll: true, guards: ['web', 'admin']));

Route::get('/posts/{post}/edit', [PostController::class, 'edit'])
    ->middleware(Permission::using('edit-post', guards: 'api'));

Route::get('/reports', [ReportController::class, 'index'])
    ->middleware(Ability::using('admin|owner', ['create-post', 'edit-user'], team: $team, validateAll: true));
```

The names can be strings, arrays or backed enums, and the team can be a name, a key or a model. These methods don't need the middleware aliases.

## Middleware Return

The middleware supports two types of returns in case the check fails. You can configure the return type and the value in the `config/laratrust.php` file.

### Abort

By default the middleware aborts with a code `403` and the `middleware.handlers.abort.message` message. You can customize both by changing the `middleware.handlers.abort` values.

### Redirect

To make a redirection in case the middleware check fails, you will need to change the `middleware.handling` value to `redirect` and the `middleware.handlers.redirect.url` to the route you need to be redirected. Leaving the configuration like this:

```php
'handling' => 'redirect',
'handlers' => [
    'abort' => [
        'code' => 403,
        'message' => 'User does not have any of the necessary access rights.',
    ],
    'redirect' => [
        'url' => '/home',       // Change this to the route you need
        'message' => [          // Key value message to be flashed into the session.
            'key' => 'error',
            'content' => '',    // If the content is empty nothing will be flashed to the session.
        ],
    ],
],
```

### Translating the messages

The abort message and the redirect message content are passed through Laravel's `__()` helper, so you can use a [translation key](https://laravel.com/docs/localization) or the message itself as the key in your JSON translation files:

```php
'abort' => [
    'code' => 403,
    'message' => 'auth.unauthorized',
],
```
