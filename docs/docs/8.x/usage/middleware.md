---
sidebarDepth: 2
---

# Middleware

## Configuration

The middleware are registered automatically as `role`, `permission` and `ability` . If you want to change or customize them, go to your `config/laratrust.php` and set the `middleware.register` value to `false` and add the following to the `routeMiddleware` array in `app/Http/Kernel.php`:

```php
'role' => \Laratrust\Middleware\Role::class,
'permission' => \Laratrust\Middleware\Permission::class,
'ability' => \Laratrust\Middleware\Ability::class,
```

## Concepts

You can use a middleware to filter routes and route groups by permission, role or ability:

```php
Route::group(['prefix' => 'admin', 'middleware' => ['role:admin']], function() {
    Route::get('/', 'AdminController@welcome');
    Route::get('/manage', ['middleware' => ['permission:manage-admins'], 'uses' => 'AdminController@manageAdmins']);
});
```

If you use the pipe symbol it will be an _OR_ operation:

```php
'middleware' => ['role:admin|root']
// $user->hasRole(['admin', 'root']);

'middleware' => ['permission:edit-post|edit-user']
// $user->hasRole(['edit-post', 'edit-user']);
```

To emulate _AND_ functionality you can do:

```php
'middleware' => ['role:owner|writer,require_all']
// $user->hasRole(['owner', 'writer'], true);

'middleware' => ['permission:edit-post|edit-user,require_all']
// $user->isAbleTo(['edit-post', 'edit-user'], true);
```

For more complex situations use `ability` middleware which accepts 3 parameters; roles, permissions and options:

```php
'middleware' => ['ability:admin|owner,create-post|edit-user,require_all']
// $user->ability(['admin', 'owner'], ['create-post', 'edit-user'], true)
```

### Using Different Guards

If you want to use a different guard for the user check you can specify it as an option:

```php
'middleware' => ['role:owner|writer,require_all|guard:api']
'middleware' => ['permission:edit-post|edit-user,guard:some_new_guard']
'middleware' => ['ability:admin|owner,create-post|edit-user,require_all|guard:web']
```

## Teams

If you are using the teams feature and want to use the middleware checking for your teams, you can use:

```php
'middleware' => ['role:admin|root,my-awesome-team,require_all']
// $user->hasRole(['admin', 'root'], 'my-awesome-team', true);

'middleware' => ['permission:edit-post|edit-user,my-awesome-team,require_all']
// $user->isAbleTo(['edit-post', 'edit-user'], 'my-awesome-team', true);

'middleware' => ['ability:admin|owner,create-post|edit-user,my-awesome-team,require_all']
// $user->ability(['admin', 'owner'], ['create-post', 'edit-user'], 'my-awesome-team', true);
```

::: tip NOTE
The `require_all` and `guard` parameters are optional.
:::

## Middleware Return

The middleware supports two types of returns in case the check fails. You can configure the return type and the value in the `config/laratrust.php` file.

## Abort

By default the middleware aborts with a code `403` but you can customize it by changing the `middleware.handlers.abort.code` value.

## Redirect

To make a redirection in case the middleware check fails, you will need to change the `middleware.handling` value to `redirect` and the `middleware.handlers.redirect.url` to the route you need to be redirected. Leaving the configuration like this:

```php
'handling' => 'redirect',
'handlers' => [
    'abort' => [
        'code' => 403
    ],
    'redirect' => [
        'url' => '/home',       // Change this to the route you need
        'message' => [          // Key value message to be flashed into the session.
            'key' => 'error',
            'content' => ''     // If the content is empty nothing will be flashed to the session.
        ]
    ]
]
```
