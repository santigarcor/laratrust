Concepts
========

Set things up
--------------

Let's start by creating the following \ ``Role``\s:

.. code-block:: php

   $owner = new Role();
   $owner->name         = 'owner';
   $owner->display_name = 'Project Owner'; // optional
   $owner->description  = 'User is the owner of a given project'; // optional
   $owner->save();

   $admin = new Role();
   $admin->name         = 'admin';
   $admin->display_name = 'User Administrator'; // optional
   $admin->description  = 'User is allowed to manage and edit other users'; // optional
   $admin->save();

Now we just need to add \ ``Permission``\s to those \ ``Role``\s:

.. code-block:: php

   $createPost = new Permission();
   $createPost->name         = 'create-post';
   $createPost->display_name = 'Create Posts'; // optional
   // Allow a user to...
   $createPost->description  = 'create new blog posts'; // optional
   $createPost->save();

   $editUser = new Permission();
   $editUser->name         = 'edit-user';
   $editUser->display_name = 'Edit Users'; // optional
   // Allow a user to...
   $editUser->description  = 'edit existing users'; // optional
   $editUser->save();

   $admin->attachPermission($createPost);
   // equivalent to $admin->permissions()->sync([$createPost->id]);

   $owner->attachPermissions([$createPost, $editUser]);
   // equivalent to $owner->permissions()->sync([$createPost->id, $editUser->id]);

Without Groups
--------------

Permissions Assignment
^^^^^^^^^^^^^^^^^^^^^^
You can attach single permissions to an user, so in order to do it you only have to make:

.. code-block:: php
   $user = User::where('username', '=', 'michele')->first();

   // permission attach alias
   $user->attachPermission($createPost); // parameter can be an Permission object, array, or id

   // if you want to attach multiple permissions
   $user->attachPermissions([$createPost, $editUser]);

   // To detach permissions you can do
   $user->detachPermission($createPost]);   
   $user->detachPermissions([$createPost, $editUser]);   

Roles Assignment
^^^^^^^^^^^^^^^^

With both roles created let's assign them to the users.
Thanks to the ``LaratrustUserTrait`` this is as easy as:

.. code-block:: php

   $user = User::where('username', '=', 'michele')->first();

   // role attach alias
   $user->attachRole($admin); // parameter can be an Role object, array, or id

   // multiple roles
   $user->attachRoles([$admin, $owner]);

   // or eloquent's original technique
   $user->roles()->attach($admin->id); // id only

   // if you want to detach roles you can do
   $user->detachRole($admin);
   $user->detachRoles([$admin, $owner]);

Checking for Roles & Permissions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Now we can check for roles and permissions simply by doing:

.. code-block:: php

   $user->hasRole('owner');   // false
   $user->hasRole('admin');   // true
   $user->can('edit-user');   // false
   $user->can('create-post'); // true

Both ``hasRole()`` and ``can()`` can receive an array of roles & permissions to check:

.. code-block:: php

   $user->hasRole(['owner', 'admin']);       // true
   $user->can(['edit-user', 'create-post']); // true

By default, if any of the roles or permissions are present for a user then the method will return true.
Passing ``true`` as a second parameter instructs the method to require **all** of the items:

.. code-block:: php

   $user->hasRole(['owner', 'admin']);             // true
   $user->hasRole(['owner', 'admin'], true);       // false, user does not have admin role
   $user->can(['edit-user', 'create-post']);       // true
   $user->can(['edit-user', 'create-post'], true); // false, user does not have edit-user permission

You can have as many \ ``Role``\s as you want for each ``User`` and vice versa.

The ``Laratrust`` class has shortcuts to both ``can()`` and ``hasRole()`` for the currently logged in user:

.. code-block:: php

   Laratrust::hasRole('role-name');
   Laratrust::can('permission-name');

   // is identical to

   Auth::user()->hasRole('role-name');
   Auth::user()->can('permission-name');

You can also use placeholders (wildcards) to check any matching permission by doing:

.. code-block:: php

   // match any admin permission
   $user->can('admin.*'); // true

   // match any permission about users
   $user->can('*_users'); // true

User ability
^^^^^^^^^^^^

More advanced checking can be done using the awesome ``ability`` function.
It takes in three parameters (roles, permissions, options):
   
* ``roles`` is a set of roles to check.
* ``permissions`` is a set of permissions to check.

Either of the roles or permissions variable can be a comma separated string or array:

.. code-block:: php

   $user->ability(['admin', 'owner'], ['create-post', 'edit-user']);

   // or

   $user->ability('admin,owner', 'create-post,edit-user');

This will check whether the user has any of the provided roles and permissions.
In this case it will return true since the user is an ``admin`` and has the ``create-post`` permission.

The third parameter is an options array:

.. code-block:: php

   $options = [
       'validate_all' => true | false (Default: false),
       'return_type'  => boolean | array | both (Default: boolean)
   ];

* ``validate_all`` is a boolean flag to set whether to check all the values for true, or to return true if at least one role or permission is matched.
* ``return_type`` specifies whether to return a boolean, array of checked values, or both in an array.

Here is an example output:

.. code-block:: php

   $options = [
       'validate_all' => true,
       'return_type' => 'both'
   ];

   list($validate, $allValidations) = $user->ability(
       ['admin', 'owner'],
       ['create-post', 'edit-user'],
       $options
   );

   var_dump($validate);
   // bool(false)

   var_dump($allValidations);
   // array(4) {
   //     ['role'] => bool(true)
   //     ['role_2'] => bool(false)
   //     ['create-post'] => bool(true)
   //     ['edit-user'] => bool(false)
   // }

The ``Laratrust`` class has a shortcut to ``ability()`` for the currently logged in user:

.. code-block:: php

   Laratrust::ability('admin,owner', 'create-post,edit-user');

   // is identical to

   Auth::user()->ability('admin,owner', 'create-post,edit-user');


With Groups
-----------

First we need to create a group in order to use it in the following steps:

.. code-block:: php
   
   $group = Group::create([
      'name' => 'my-great-team',
      'display_name' => 'My Great Team'
   ]);

Roles Assignment
^^^^^^^^^^^^^^^^

With the group created to attach it to a user is as easy as:

.. code-block:: php

   $user = User::where('username', '=', 'michele')->first();

   // First parameter can be a Role or an id
   // Second parameter must be a Group
   $user->attachRole($admin, $group);

   // multiple roles
   $user->attachRoles([$admin, $owner], $group);

   // if you want to detach roles you can do
   $user->detachRole($admin, $group);
   $user->detachRoles([$admin, $owner], $group);

Checking for Roles & Permissions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Now we can check for roles and permissions inside a group simply by doing:

.. code-block:: php

   $user->hasRole('admin');                    // false
   $user->hasRole('admin', 'my-great-team');   // true
   $user->hasRole('owner', 'my-great-team');   // false
   $user->can('edit-user', 'my-great-team');   // false
   $user->can('create-post', 'my-great-team'); // true

Both ``hasRole()`` and ``can()`` can receive an array of roles & permissions to check:

.. code-block:: php

   $user->hasRole(['owner', 'admin'], 'my-great-team');       // true
   $user->can(['edit-user', 'create-post'], 'my-great-team'); // true
   $user->hasRole(['owner', 'admin']);                        // false
   $user->can(['edit-user', 'create-post']);                  // false

By default, if any of the roles or permissions are present for a user then the method will return true.
Passing ``true`` as a third parameter instructs the method to require **all** of the items within that group:

.. code-block:: php

   $user->hasRole(['owner', 'admin'], 'my-great-team');             // true
   $user->hasRole(['owner', 'admin'], 'my-great-team', true);       // false, user does not have owner role
   $user->can(['edit-user', 'create-post'], 'my-great-team');       // true
   $user->can(['edit-user', 'create-post'], 'my-great-team', true); // false, user does not have edit-user permission

You can have as many \ ``Role``\s as you want for each ``User`` and vice versa.

The ``Laratrust`` class has shortcuts to both ``can()`` and ``hasRole()`` for the currently logged in user:

.. code-block:: php

   Laratrust::hasRole('role-name', 'my-great-team');
   Laratrust::can('permission-name', 'my-great-team');

   // is identical to

   Auth::user()->hasRole('role-name', 'my-great-team');
   Auth::user()->can('permission-name', 'my-great-team');

You can also use placeholders (wildcards) to check any matching permission by doing:

.. code-block:: php

   // match any admin permission
   $user->can('admin.*', 'my-great-team'); // true

   // match any permission about users
   $user->can('*_users', 'my-great-team'); // false

User ability
^^^^^^^^^^^^

More advanced checking can be done using the awesome ``ability`` function.
It takes in four parameters (roles, permissions, group, options):
   
* ``roles`` is a set of roles to check.
* ``permissions`` is a set of permissions to check.
* ``group`` is the name of the group to have in mind when checking the roles and permissions.

Either of the roles or permissions variable can be a comma separated string or array:

.. code-block:: php

   $user->ability(['admin', 'owner'], ['create-post', 'edit-user'], 'my-great-team');

   // or

   $user->ability('admin,owner', 'create-post,edit-user', 'my-great-team');

This will check whether the user has any of the provided roles and permissions within ``my-great-team`` group.
In this case it will return true since the user is an ``admin`` and has the ``create-post`` permission.

The fourth parameter is an options array:

.. code-block:: php

   $options = [
       'validate_all' => true | false (Default: false),
       'return_type'  => boolean | array | both (Default: boolean)
   ];

* ``validate_all`` is a boolean flag to set whether to check all the values for true, or to return true if at least one role or permission is matched.
* ``return_type`` specifies whether to return a boolean, array of checked values, or both in an array.

Here is an example output:

.. code-block:: php

   $options = [
       'validate_all' => true,
       'return_type' => 'both'
   ];

   list($validate, $allValidations) = $user->ability(
       ['admin', 'owner'],
       ['create-post', 'edit-user'],
       'my-great-team'
       $options
   );

   var_dump($validate);
   // bool(false)

   var_dump($allValidations);
   // array(4) {
   //     ['role'] => bool(true)
   //     ['role_2'] => bool(false)
   //     ['create-post'] => bool(true)
   //     ['edit-user'] => bool(false)
   // }

The ``Laratrust`` class has a shortcut to ``ability()`` for the currently logged in user:

.. code-block:: php

   Laratrust::ability('admin,owner', 'create-post,edit-user', 'my-great-team');

   // is identical to

   Auth::user()->ability('admin,owner', 'create-post,edit-user', 'my-great-team');

Model's Ownership
-----------------

If you need to check if the user owns a model you can use the user function ``owns``:

.. code-block:: php
   
    public function update (Post $post) {
        if ($user->owns($post)) {
            abort(403);
        }

    ...
    }