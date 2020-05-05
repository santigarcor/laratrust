---
sidebarDepth: 2
---

# Objects Ownership
If you need to check if the user owns an object you can use the user function `owns`:

```php
public function update (Post $post) {
    if ($user->owns($post)) { //This will check the 'user_id' inside the $post
        abort(403);
    }

    ...
}
```

If you want to change the foreign key name to check for, you can pass a second attribute to the method:

```php
public function update (Post $post) {
    if ($user->owns($post, 'idUser')) { //This will check for 'idUser' inside the $post
        abort(403);
    }

    ...
}
```

## Permissions, Roles & Ownership Checks
If you want to check if a user can do something or has a role, and also is the owner of an object you can use the `isAbleToAndOwns` and `hasRoleAndOwns` methods:

Both methods accept three parameters:

* `permission` or `role` are the permission or role to check (This can be an array of roles or permissions).
* `thing` is the object used to check the ownership.
* `options` is a set of options to change the method behavior (optional).

The third parameter is an options array:

```php
$options = [
    'requireAll' => true, //Default: false,
    'foreignKeyName'  => 'canBeAnyString' //Default: null
];
```

Here's an example of the usage of both methods:

```php
$post = Post::find(1);
$user->isAbleToAndOwns('edit-post', $post);
$user->isAbleToAndOwns(['edit-post', 'delete-post'], $post);
$user->isAbleToAndOwns(['edit-post', 'delete-post'], $post, ['requireAll' => false, 'foreignKeyName' => 'writer_id']);

$user->hasRoleAndOwns('admin', $post);
$user->hasRoleAndOwns(['admin', 'writer'], $post);
$user->hasRoleAndOwns(['admin', 'writer'], $post, ['requireAll' => false, 'foreignKeyName' => 'writer_id']);
```

The `Laratrust` class has a shortcut to `owns()`, `isAbleToAndOwns` and `hasRoleAndOwns` methods for the currently logged in user:

```php
Laratrust::owns($post);
Laratrust::owns($post, 'idUser');

Laratrust::isAbleToAndOwns('edit-post', $post);
Laratrust::isAbleToAndOwns(['edit-post', 'delete-post'], $post, ['requireAll' => false, 'foreignKeyName' => 'writer_id']);

Laratrust::hasRoleAndOwns('admin', $post);
Laratrust::hasRoleAndOwns(['admin', 'writer'], $post, ['requireAll' => false, 'foreignKeyName' => 'writer_id']);
```

## Ownable Interface
If the object ownership is resolved through a more complex logic you can implement the Ownable interface so you can use the `owns`, `isAbleToAndOwns` and `hasRoleAndOwns` methods in those cases:

```php
class SomeOwnedObject implements \Laratrust\Contracts\Ownable
{
    ...

    public function ownerKey($owner)
    {
        return $this->someRelationship->user->id;
    }

    ...
}
```

::: tip IMPORTANT
- The `ownerKey` method **must** return the object's owner id value.
- The `ownerKey` method receives as a parameter the object that called the `owns` method.
:::

After implementing it, you simply do:

```php
$user = User::find(1);
$theObject = new SomeOwnedObject;
$user->owns($theObject);            // This will return true or false depending on what the ownerKey method returns
```