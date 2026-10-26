---
sidebarDepth: 2
---

# Roles & Permissions

## Setting things up

Let's start by creating the following `Role`s:

By default, the paths for the Role and Permission models will be `App\Models\Role` and `App\Models\Permission`. If you want to modify them, you can go to `config/laratrust.php`.

```php
use App\Models\Role;

$owner = Role::create([
    'name' => 'owner',
    'display_name' => 'Project Owner', // optional
    'description' => 'User is the owner of a given project', // optional
]);

$admin = Role::create([
    'name' => 'admin',
    'display_name' => 'User Administrator', // optional
    'description' => 'User is allowed to manage and edit other users', // optional
]);
```

Now we need to add `Permission`s:

```php
use App\Models\Permission;

$createPost = Permission::create([
    'name' => 'create-post',
    'display_name' => 'Create Posts', // optional
    'description' => 'create new blog posts', // optional
]);

$editUser = Permission::create([
    'name' => 'edit-user',
    'display_name' => 'Edit Users', // optional
    'description' => 'edit existing users', // optional
]);
```

## Role Permissions Assignment & Removal

### Assignment

```php
$admin->givePermission($createPost); // parameter can be a Permission object, array or id
// equivalent to $admin->permissions()->attach([$createPost->id]);

$owner->givePermissions([$createPost, $editUser]); // parameter can be a Permission object, array or id
// equivalent to $owner->permissions()->attach([$createPost->id, $editUser->id]);

$owner->syncPermissions([$createPost, $editUser]); // parameter can be a Permission object, array or id
// equivalent to $owner->permissions()->sync([$createPost->id, $editUser->id]);
```

### Removal

```php
$admin->removePermission($createPost); // parameter can be a Permission object, array or id
// equivalent to $admin->permissions()->detach([$createPost->id]);

$owner->removePermissions([$createPost, $editUser]); // parameter can be a Permission object, array or id
// equivalent to $owner->permissions()->detach([$createPost->id, $editUser->id]);
```

Giving a role a permission it already has does nothing. `removePermissions([])` removes nothing: to remove all the permissions, use `$owner->syncPermissions([])`.

## User Roles Assignment & Removal

With both roles created let's assign them to the users.

### Assignment

```php
$user->addRole($admin); // parameter can be a Role object, BackedEnum, array, id or the role string name
// equivalent to $user->roles()->attach([$admin->id]);

$user->addRoles([$admin, $owner]); // parameter can be a Role object, BackedEnum, array, id or the role string name
// equivalent to $user->roles()->attach([$admin->id, $owner->id]);

$user->syncRoles([$admin->id, $owner->id]);
// equivalent to $user->roles()->sync([$admin->id, $owner->id]);

$user->syncRolesWithoutDetaching([$admin->id, $owner->id]);
// equivalent to $user->roles()->syncWithoutDetaching([$admin->id, $owner->id]);
```

### Removal

```php
$user->removeRole($admin); // parameter can be a Role object, BackedEnum, array, id or the role string name
// equivalent to $user->roles()->detach([$admin->id]);

$user->removeRoles([$admin, $owner]); // parameter can be a Role object, BackedEnum, array, id or the role string name
// equivalent to $user->roles()->detach([$admin->id, $owner->id]);
```

Adding a role the user already has does nothing, and dispatches no event. `removeRoles([])` removes nothing: to remove all the roles, use `$user->syncRoles([])`.

## User Permissions Assignment & Removal

You can give single permissions to a user, so in order to do it you only have to make:

### Assignment

```php
$user->givePermission($editUser); // parameter can be a Permission object, array, id or the permission string name
// equivalent to $user->permissions()->attach([$editUser->id]);

$user->givePermissions([$editUser, $createPost]); // parameter can be a Permission object, array, id or the permission string name
// equivalent to $user->permissions()->attach([$editUser->id, $createPost->id]);

$user->syncPermissions([$editUser->id, $createPost->id]);
// equivalent to $user->permissions()->sync([$editUser->id, $createPost->id]);

$user->syncPermissionsWithoutDetaching([$editUser, $createPost]); // parameter can be a Permission object, array or id
    // equivalent to $user->permissions()->syncWithoutDetaching([$createPost->id, $editUser->id]);
```

### Removal

```php
$user->removePermission($createPost); // parameter can be a Permission object, array, id or the permission string name
// equivalent to $user->permissions()->detach([$createPost->id]);

$user->removePermissions([$createPost, $editUser]); // parameter can be a Permission object, array, id or the permission string name
// equivalent to $user->permissions()->detach([$createPost->id, $editUser->id]);
```

Like with the roles, giving a permission the user already has does nothing, and `removePermissions([])` removes nothing: to remove them all, use `$user->syncPermissions([])`.

## Checking for Roles & Permissions

Now we can check for roles and permissions simply by doing:

```php
$user->hasRole('owner');   // false
$user->hasRole('admin');   // true
$user->hasPermission('edit-user');   // false
$user->isAbleTo('edit-user');   // false
$user->hasPermission('create-post'); // true
$user->isAbleTo('create-post'); // true
$user->doesntHavePermission('create-post'); // false
$user->isNotAbleTo('create-post'); // false
```

::: tip NOTE
If you want, you can use the `hasPermission` or `isAbleTo`.
:::

::: tip NOTE
We dropped the usage of the `can` method in order to have full support to Laravel's Gates and Policies.
:::

Both `hasPermission()` and `hasRole()` can receive an array or pipe separated string of roles & permissions to check:

```php
$user->hasRole(['owner', 'admin']);       // true
$user->hasPermission(['edit-user', 'create-post']); // true

$user->hasRole('owner|admin');       // true
$user->hasPermission('edit-user|create-post'); // true
```

By default, if any of the roles or permissions are present for a user then the method will return true.
The `requireAll` argument instructs the method to require **all** of the items:

```php
$user->hasRole(['owner', 'admin']);                    // true
$user->hasRole(['owner', 'admin'], requireAll: true);  // false, user does not have owner role
$user->hasPermission(['edit-user', 'create-post']);                    // true
$user->hasPermission(['edit-user', 'create-post'], requireAll: true);  // false, user does not have edit-user permission
$user->doesntHavePermission(['edit-user', 'create-post']);                   // false, user does have create-post permission
$user->doesntHavePermission(['edit-user', 'create-post'], requireAll: true); // true, user does not have edit-user permission
```

::: tip NOTE
The second argument is the team. When you don't use teams, you can also pass `requireAll` as the second argument: `$user->hasRole(['owner', 'admin'], true)`.
:::

The names can also be [backed enums](https://www.php.net/manual/en/language.enumerations.backed.php), or arrays of them:

```php
$user->hasRole(RoleName::Admin);
$user->hasPermission([PermissionName::EditUser, PermissionName::CreatePost]);
```

You can have as many `Role`s as you want for each `User` and vice versa. Also, you can have as many direct `Permissions`s as you want for each `User` and vice versa.

The `Laratrust` class has shortcuts to `hasRole()`, `hasPermission()`, `doesntHavePermission()`, `isAbleTo()` and `isNotAbleTo()` for the user authenticated with the default guard. When nobody is authenticated, they return `false`:

```php
Laratrust::hasRole('role-name');
Laratrust::hasPermission('permission-name');
Laratrust::doesntHavePermission('permission-name');

// is identical to

Auth::user()->hasRole('role-name');
Auth::user()->hasPermission('permission-name');
Auth::user()->doesntHavePermission('permission-name');
```

To get the names of the user's roles, use `getRoles()`:

```php
$user->getRoles(); // ['admin']
```

You can also use a wildcard to check any matching permission by doing:

```php
// match any admin permission
$user->hasPermission('admin.*'); // true

// match any permission about users
$user->hasPermission('*-users'); // true
```

::: tip NOTE
Only the names you check can have wildcards. A permission named `admin.*` doesn't give the user every `admin.` permission.
:::

## Laravel Gates

If you set the `permissions_as_gates` value to `true` in your `config/laratrust.php` file, Laravel's Gate checks also check the Laratrust permissions. So you can use `can()`, `@can` and the `can` middleware with the permission names:

```php
$user->can('edit-user');
$user->can('edit-user', 'my-awesome-team'); // With a team.
$user->can('edit-user', [$team, true]); // With a team and requireAll.
```

When the user doesn't have the permission, Laravel goes on with your gates and policies, so they keep working.

The team is the first argument that is a team model, a backed enum, a uuid, an id or a name. Other models are what your policies check, so they're never taken for the team: `$user->can('edit-post', $post)` checks the `edit-post` permission without a team, never in the team whose id is `$post->id`.

## User ability

More advanced checking can be done using the awesome `ability` function.
It takes in three parameters (roles, permissions, options):

- `roles` is a set of roles to check.
- `permissions` is a set of permissions to check.
- `options` is a set of options to change the method behavior.

Either of the roles or permissions variable can be a pipe separated string or an array:

```php
$user->ability(['admin', 'owner'], ['create-post', 'edit-user']);

// or

$user->ability('admin|owner', 'create-post|edit-user');
```

This will check whether the user has any of the provided roles and permissions.
In this case it will return true since the user is an `admin` and has the `create-post` permission.

The options array comes after the team. When you don't use teams, you can pass it as the third argument:

```php
$options = [
    'validate_all' => true, //Default: false
    'return_type'  => 'array' //Default: 'boolean'. You can also set it as 'both'
];
```

- `validate_all` is a boolean flag to set whether to check all the values for true, or to return true if at least one role or permission is matched.
- `return_type` specifies whether to return a boolean, array of checked values, or both in an array.

Here is an example output:

```php
$options = [
    'validate_all' => true,
    'return_type' => 'both'
];

[$validate, $allValidations] = $user->ability(
    ['admin', 'owner'],
    ['create-post', 'edit-user'],
    options: $options
);

var_dump($validate);
// bool(false)

var_dump($allValidations);
// array(2) {
//     ['roles'] => ['admin' => true, 'owner' => false],
//     ['permissions'] => ['create-post' => true, 'edit-user' => false],
// }
```

If `validate_all` isn't a boolean or `return_type` isn't one of `boolean`, `array` or `both`, the method throws an `InvalidArgumentException`.

The `Laratrust` class has a shortcut to `ability()` for the currently logged in user:

```php
Laratrust::ability('admin|owner', 'create-post|edit-user');

// is identical to

Auth::user()->ability('admin|owner', 'create-post|edit-user');
```

## Cache

Laratrust loads the roles and permissions of a user once per request (or queue job), with at most 4 queries, however many roles the user has. All the checks in that request use this copy, so they don't query the database again. Laravel resets it between requests, queue jobs and [Octane](https://laravel.com/docs/octane) requests.

When the cache is enabled, Laratrust also stores the roles and permissions in your application cache, so the next requests don't query the database either. The cache is configured in the `cache` section of the `config/laratrust.php` file:

```php
'cache' => [
    'enabled' => env('LARATRUST_ENABLE_CACHE', env('APP_ENV') === 'production'),
    'expiration_time' => 3600, // In seconds.
],
```

The name and the permissions of each role are cached once, not copied into each user. When you rename a role, delete it or change its permissions, all the users with that role see the change.

### Flushing the cache

Laratrust flushes the cache when you use its methods to add, remove or sync roles and permissions, and when:

- A user or a role is saved, deleted or restored.
- A permission is renamed, deleted or restored. This flushes the roles and the users that have it.
- A team is force deleted. This flushes the users with roles or permissions within it.

If you change the pivot tables in another way, e.g. with the relationships, flush the cache yourself:

```php
$user->roles()->attach($admin->id);
$user->flushLaratrustCache();

$admin->permissions()->detach($createPost->id);
$admin->flushLaratrustCache();
```

::: tip NOTE
You have to flush also when the cache is disabled, because the roles and permissions are kept for the current request.
:::

::: warning
A request that reads the roles and permissions from the database while another one changes them can put the old ones back in the cache, right after the change flushed it. They stay there until they expire (`expiration_time`), or until the next change of that user or role flushes them again. This window is very short, but if a revoked permission must never be used again, keep `expiration_time` low, or flush the user again a moment after the change, e.g. from a delayed job.
:::

### Long-running processes

Laravel forgets the loaded roles and permissions between requests, queue jobs and Octane requests. A process that doesn't go through any of them, e.g. a daemon command that loops, keeps them until it calls `Laratrust::reset()`:

```php
use Laratrust\LaratrustFacade as Laratrust;

while (true) {
    Laratrust::reset(); // The next checks read the cache or the database again.

    // ...
}
```

### Cache keys

By default the cache keys are `laratrust_user_{key}_{id}` for the users, where `{key}` is the user model key in `user_models`, and `laratrust_role_{id}` for the roles. To change them, e.g. to keep the tenants apart in a multi-tenant application, override the `laratrustCacheKey()` method in your user and role models:

```php
class User extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions;

    public function laratrustCacheKey(): string
    {
        // tenant() stands for your tenancy package's helper.
        return 'tenant_'.tenant()->id.'_laratrust_user_'.$this->getKey();
    }
}

class Role extends RoleModel
{
    public function laratrustCacheKey(): string
    {
        return 'tenant_'.tenant()->id.'_laratrust_role_'.$this->getKey();
    }
}
```

::: warning
Use only the model's key and your application's context (like the current tenant) in `laratrustCacheKey()`, not other attributes. To flush them, Laratrust loads the users and roles with only their key.
:::
