# Short syntax route filter

To filter a route by permission or role you can call the following in your `app/Http/routes.php`:

```php
// only users with roles that have the 'manage_posts' permission will be able to access any route within admin/post
Laratrust::routeNeedsPermission('admin/post*', 'create-post');

// only owners will have access to routes within admin/advanced
Laratrust::routeNeedsRole('admin/advanced*', 'owner');

// optionally the second parameter can be an array of permissions or roles
// user would need to match all roles or permissions for that route
Laratrust::routeNeedsPermission('admin/post*', array('create-post', 'edit-comment'));
Laratrust::routeNeedsRole('admin/advanced*', array('owner','writer'));
```

Both of these methods accept a third parameter.
If the third parameter is null then the return of a prohibited access will be `App::abort(403)`, otherwise the third parameter will be returned.
So you can use it like:

```php
Laratrust::routeNeedsRole('admin/advanced*', 'owner', Redirect::to('/home'));
```

Furthermore both of these methods accept a fourth parameter.
It defaults to true and checks all roles/permissions given.
If you set it to false, the function will only fail if all roles/permissions fail for that user.
Useful for admin applications where you want to allow access for multiple groups.

```php
// if a user has 'create-post', 'edit-comment', or both they will have access
Laratrust::routeNeedsPermission('admin/post*', array('create-post', 'edit-comment'), null, false);

// if a user is a member of 'owner', 'writer', or both they will have access
Laratrust::routeNeedsRole('admin/advanced*', array('owner','writer'), null, false);

// if a user is a member of 'owner', 'writer', or both, or user has 'create-post', 'edit-comment' they will have access
// if the 4th parameter is true then the user must be a member of Role and must have Permission
Laratrust::routeNeedsRoleOrPermission(
    'admin/advanced*',
    array('owner', 'writer'),
    array('create-post', 'edit-comment'),
    null,
    false
);
```