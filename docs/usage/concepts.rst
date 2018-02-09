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

Now we need to add \ ``Permission``\s:

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

Role Permissions Assignment & Removal
-------------------------------------
By using the ``LaratrustRoleTrait`` we can do the following:

Assignment
^^^^^^^^^^

.. code-block:: php

    $admin->attachPermission($createPost); // parameter can be a Permission object, array or id
    // equivalent to $admin->permissions()->attach([$createPost->id]);

    $owner->attachPermissions([$createPost, $editUser]); // parameter can be a Permission object, array or id
    // equivalent to $owner->permissions()->attach([$createPost->id, $editUser->id]);

    $owner->syncPermissions([$createPost, $editUser]); // parameter can be a Permission object, array or id
    // equivalent to $owner->permissions()->sync([$createPost->id, $editUser->id]);

Removal
^^^^^^^

.. code-block:: php

    $admin->detachPermission($createPost); // parameter can be a Permission object, array or id
    // equivalent to $admin->permissions()->detach([$createPost->id]);

    $owner->detachPermissions([$createPost, $editUser]); // parameter can be a Permission object, array or id
    // equivalent to $owner->permissions()->detach([$createPost->id, $editUser->id]);

User Roles Assignment & Removal
-------------------------------

With both roles created let's assign them to the users.
Thanks to the ``LaratrustUserTrait`` this is as easy as:

Assignment
^^^^^^^^^^

.. code-block:: php

    $user->attachRole($admin); // parameter can be a Role object, array, id or the role string name
    // equivalent to $user->roles()->attach([$admin->id]);

    $user->attachRoles([$admin, $owner]); // parameter can be a Role object, array, id or the role string name
    // equivalent to $user->roles()->attach([$admin->id, $owner->id]);

    $user->syncRoles([$admin->id, $owner->id]);
    // equivalent to $user->roles()->sync([$admin->id, $owner->id]);

    $user->syncRolesWithoutDetaching([$admin->id, $owner->id]);
    // equivalent to $user->roles()->syncWithoutDetaching([$admin->id, $owner->id]);

Removal
^^^^^^^

.. code-block:: php

    $user->detachRole($admin); // parameter can be a Role object, array, id or the role string name
    // equivalent to $user->roles()->detach([$admin->id]);

    $user->detachRoles([$admin, $owner]); // parameter can be a Role object, array, id or the role string name
    // equivalent to $user->roles()->detach([$admin->id, $owner->id]);

User Permissions Assignment & Removal
-------------------------------------

You can attach single permissions to a user, so in order to do it you only have to make:

Assignment
^^^^^^^^^^

.. code-block:: php

    $user->attachPermission($editUser); // parameter can be a Permission object, array, id or the permission string name
    // equivalent to $user->permissions()->attach([$editUser->id]);

    $user->attachPermissions([$editUser, $createPost]); // parameter can be a Permission object, array, id or the permission string name
    // equivalent to $user->permissions()->attach([$editUser->id, $createPost->id]);

    $user->syncPermissions([$editUser->id, $createPost->id]);
    // equivalent to $user->permissions()->sync([$editUser->id, createPost->id]);

    $user->syncPermissionsWithoutDetaching([$editUser, $createPost]); // parameter can be a Permission object, array or id
    // equivalent to $user->permissions()->syncWithoutDetaching([$createPost->id, $editUser->id]);

Removal
^^^^^^^

.. code-block:: php

    $user->detachPermission($createPost); // parameter can be a Permission object, array, id or the permission string name
    // equivalent to $user->roles()->detach([$createPost->id]);

    $user->detachPermissions([$createPost, $editUser]); // parameter can be a Permission object, array, id or the permission string name
    // equivalent to $user->roles()->detach([$createPost->id, $editUser->id]);

Checking for Roles & Permissions
--------------------------------

Now we can check for roles and permissions simply by doing:

.. code-block:: php

    $user->hasRole('owner');   // false
    $user->hasRole('admin');   // true
    $user->can('edit-user');   // false
    $user->can('create-post'); // true

.. NOTE::
    - If you want, you can use the ``hasPermission`` and ``isAbleTo`` methods instead of the ``can`` method.
    - If you want, you can use the ``isA`` and ``isAn`` methods instead of the ``hasRole`` method.

.. NOTE::
    If you want to use the Authorizable trait alongside Laratrust please check the  :ref:`troubleshooting <troubleshooting>` page.

Both ``can()`` and ``hasRole()`` can receive an array or pipe separated string of roles & permissions to check:

.. code-block:: php

    $user->hasRole(['owner', 'admin']);       // true
    $user->can(['edit-user', 'create-post']); // true

    $user->hasRole('owner|admin');       // true
    $user->can('edit-user|create-post'); // true

By default, if any of the roles or permissions are present for a user then the method will return true.
Passing ``true`` as a second parameter instructs the method to require **all** of the items:

.. code-block:: php

    $user->hasRole(['owner', 'admin']);             // true
    $user->hasRole(['owner', 'admin'], true);       // false, user does not have admin role
    $user->can(['edit-user', 'create-post']);       // true
    $user->can(['edit-user', 'create-post'], true); // false, user does not have edit-user permission

You can have as many \ ``Role``\s as you want for each ``User`` and vice versa. Also, you can have as many direct \ ``Permissions``\s as you want for each ``User`` and vice versa.

The ``Laratrust`` class has shortcuts to both ``can()`` and ``hasRole()`` for the currently logged in user:

.. code-block:: php

    Laratrust::hasRole('role-name');
    Laratrust::can('permission-name');

    // is identical to

    Auth::user()->hasRole('role-name');
    Auth::user()->hasPermission('permission-name');

.. WARNING::
    There aren't  ``Laratrust::hasPermission`` or ``Laratrust::isAbleTo`` facade methods, because you can use the ``Laratrust::can`` even when using the ``Authorizable`` trait.

You can also use wildcard to check any matching permission by doing:

.. code-block:: php

    // match any admin permission
    $user->can('admin.*'); // true

    // match any permission about users
    $user->can('*_users'); // true

Magic can method
^^^^^^^^^^^^^^^^

You can check if a user has some permissions by using the magic can method:

.. code-block:: php

    $user->canCreateUsers();
    // Same as $user->can('create-users');

If you want to change the case used when checking for the permission, you can change the ``magic_can_method_case`` value in your ``config/laratrust.php`` file.

.. code-block:: php

    // config/laratrust.php
    'magic_can_method_case' => 'snake_case', // The default value is 'kebab_case'

    // In you controller
    $user->canCreateUsers();
    // Same as $user->can('create_users');

User ability
------------

More advanced checking can be done using the awesome ``ability`` function.
It takes in three parameters (roles, permissions, options):

* ``roles`` is a set of roles to check.
* ``permissions`` is a set of permissions to check.
* ``options`` is a set of options to change the method behavior.

Either of the roles or permissions variable can be a pipe separated string or an array:

.. code-block:: php

    $user->ability(['admin', 'owner'], ['create-post', 'edit-user']);

    // or

    $user->ability('admin|owner', 'create-post|edit-user');

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

    Laratrust::ability('admin|owner', 'create-post|edit-user');

    // is identical to

    Auth::user()->ability('admin|owner', 'create-post|edit-user');

Retrieving Relationships
------------------------

The ``LaratrustUserTrait`` has the ``roles`` and ``permissions`` relationship, that return a ``MorphToMany`` relationships.

The ``roles`` relationship has all the roles attached to the user.

The ``permissions`` relationship has all the direct permissions attached to the user.

If you want to retrieve all the user permissions, you can use the ``allPermissions`` method. It returns a unified collection with all the permissions related to the user (via the roles and permissions relationships).

.. code-block:: php

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

If you want to retrieve the users that have some role you can use the query scope ``whereRoleIs``:

.. code-block:: php

    // This will return the users with 'admin' role.
    $users = User::whereRoleIs('admin')->get();

Also, if you want to retrieve the users that have some permission you can use the query scope ``wherePermissionIs``:

.. code-block:: php

    // This will return the users with 'edit-user' permission.
    $users = User::wherePermissionIs('edit-user')->get();

Objects Ownership
-------------------

If you need to check if the user owns an object you can use the user function ``owns``:

.. code-block:: php

    public function update (Post $post) {
        if ($user->owns($post)) { //This will check the 'user_id' inside the $post
           abort(403);
        }

        ...
    }

If you want to change the foreign key name to check for, you can pass a second attribute to the method:

.. code-block:: php

    public function update (Post $post) {
        if ($user->owns($post, 'idUser')) { //This will check for 'idUser' inside the $post
            abort(403);
        }

        ...
    }

Permissions, Roles & Ownership Checks
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you want to check if a user can do something or has a role, and also is the owner of an object you can use the ``canAndOwns`` and ``hasRoleAndOwns`` methods:

Both methods accept three parameters:

* ``permission`` or ``role`` are the permission or role to check (This can be an array of roles or permissions).
* ``thing`` is the object used to check the ownership.
* ``options`` is a set of options to change the method behavior (optional).

The third parameter is an options array:

.. code-block:: php

    $options = [
        'requireAll' => true | false (Default: false),
        'foreignKeyName'  => 'canBeAnyString' (Default: null)
    ];

Here's an example of the usage of both methods:

.. code-block:: php

    $post = Post::find(1);
    $user->canAndOwns('edit-post', $post);
    $user->canAndOwns(['edit-post', 'delete-post'], $post);
    $user->canAndOwns(['edit-post', 'delete-post'], $post, ['requireAll' => false, 'foreignKeyName' => 'writer_id']);

    $user->hasRoleAndOwns('admin', $post);
    $user->hasRoleAndOwns(['admin', 'writer'], $post);
    $user->hasRoleAndOwns(['admin', 'writer'], $post, ['requireAll' => false, 'foreignKeyName' => 'writer_id']);


The ``Laratrust`` class has a shortcut to ``owns()``, ``canAndOwns`` and ``hasRoleAndOwns`` methods for the currently logged in user:

.. code-block:: php

    Laratrust::owns($post);
    Laratrust::owns($post, 'idUser');

    Laratrust::canAndOwns('edit-post', $post);
    Laratrust::canAndOwns(['edit-post', 'delete-post'], $post, ['requireAll' => false, 'foreignKeyName' => 'writer_id']);

    Laratrust::hasRoleAndOwns('admin', $post);
    Laratrust::hasRoleAndOwns(['admin', 'writer'], $post, ['requireAll' => false, 'foreignKeyName' => 'writer_id']);

Ownable Interface
^^^^^^^^^^^^^^^^^

If the object ownership is resolved through a more complex logic you can implement the Ownable interface so you can use the ``owns``, ``canAndOwns`` and ``hasRoleAndOwns`` methods in those cases:

.. code-block:: php

    class SomeOwnedObject implements \Laratrust\Contracts\Ownable
    {
       ...

       public function ownerKey($owner)
       {
            return $this->someRelationship->user->id;
       }

       ...
    }

.. IMPORTANT::
    - The ``ownerKey`` method **must** return the object's owner id value.
    - The ``ownerKey`` method receives as a parameter the object that called the ``owns`` method.

After implementing it, you can simply do:

.. code-block:: php

    $user = User::find(1);
    $theObject = new SomeOwnedObject;
    $user->owns($theObject);            // This will return true or false depending on what the ownerKey method returns

.. _teams-concepts:

Teams
-----

.. NOTE::
    The teams feature is **optional**, please go to the :ref:`teams configuration <teams-configuration>` in order to use the feature.

Roles Assignment & Removal
^^^^^^^^^^^^^^^^^^^^^^^^^^

The roles assignment and removal are the same, but this time you can pass the team as an optional parameter.

.. code-block:: php

    $team = Team::where('name', 'my-awesome-team')->first();
    $admin = Role::where('name', 'admin')->first();

    $user->attachRole($admin, $team); // parameter can be an object, array, id or the string name.

This will attach the ``admin`` role to the user but only within the ``my-awesome-team`` team.

You can also attach multiple roles to the user within a team:

.. code-block:: php

    $team = Team::where('name', 'my-awesome-team')->first();
    $admin = Role::where('name', 'admin')->first();
    $owner = Role::where('name', 'owner')->first();

    $user->attachRoles([$admin, $owner], $team); // parameter can be an object, array, id or the string name.

To remove the roles you can do:

.. code-block:: php

    $user->detachRole($admin, $team); // parameter can be an object, array, id or the string name.
    $user->detachRoles([$admin, $owner], $team); // parameter can be an object, array, id or the string name.

.. _new-sync-behavior:

You can also sync roles within a group:

.. code-block:: php

    $user->syncRoles([$admin, $owner], $team); // parameter can be an object, array, id or the string name.

.. IMPORTANT::
    It will sync the roles depending of the team passed, because there is a ``wherePivot`` constraint in the syncing method. So if you pass a team with id of 1, it will sync all the roles that are attached to the user where the team id is 1.

    So if you don't pass any team, it will sync the roles where the team id is ``null`` in the pivot table.

Permissions Assignment & Removal
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The permissions assignment and removal are the same, but this time you can pass the team as an optional parameter.

.. code-block:: php

    $team = Team::where('name', 'my-awesome-team')->first();
    $editUser = Permission::where('name', 'edit-user')->first();

    $user->attachPermission($editUser, $team); // parameter can be an object, array, id or the string name.

This will attach the ``edit-user`` permission to the user but only within the ``my-awesome-team`` team.

You can also attach multiple permissions to the user within a team:

.. code-block:: php

    $team = Team::where('name', 'my-awesome-team')->first();
    $editUser = Permission::where('name', 'edit-user')->first();
    $manageUsers = Permission::where('name', 'manage-users')->first();

    $user->attachPermissions([$editUser, $manageUsers], $team); // parameter can be an object, array, id or the string name.

To remove the permissions you can do:

.. code-block:: php

    $user->detachPermission($editUser, $team); // parameter can be an object, array, id or the string name.
    $user->detachPermissions([$editUser, $manageUsers], $team); // parameter can be an object, array, id or the string name.

You can also sync permissions within a group:

.. code-block:: php

    $user->syncPermissions([$editUser, $manageUsers], $team); // parameter can be an object, array, id or the string name.

.. IMPORTANT::
    It will sync the permissions depending of the team passed, because there is a ``wherePivot`` constraint in the syncing method. So if you pass a team with id of 1, it will sync all the permissions that are attached to the user where the team id is 1 in the pivot table.

    So if you don't pass any team, it will sync the permissions where the team id is ``null`` in the pivot table.

Checking Roles & Permissions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The roles and permissions verification is the same, but this time you can pass the team parameter.

The teams roles and permissions check can be configured by changing the ``teams_strict_check`` value inside the ``config/laratrust.php`` file. This value can be ``true`` or ``false``:

- If ``teams_strict_check`` is set to ``false``:

    When checking for a role or permission if no team is given, it will check if the user has the role or permission regardless if that role or permissions was attached inside a team.

- If ``teams_strict_check`` is set to ``true``:

    When checking for a role or permission if no team is given, it will check if the user has the role or permission where the team id is null.

Check roles:

.. code-block:: php

    $user->hasRole('admin', 'my-awesome-team');
    $user->hasRole(['admin', 'user'], 'my-awesome-team', true);

Check permissions:

.. code-block:: php

    $user->can('edit-user', 'my-awesome-team');
    $user->can(['edit-user', 'manage-users'], 'my-awesome-team', true);

User Ability
^^^^^^^^^^^^

The user ability is the same, but this time you can pass the team parameter.

.. code-block:: php

    $options = [
        'requireAll' => true | false (Default: false),
        'foreignKeyName'  => 'canBeAnyString' (Default: null)
    ];

    $user->ability(['admin'], ['edit-user'], 'my-awesome-team');
    $user->ability(['admin'], ['edit-user'], 'my-awesome-team', $options);

Permissions, Roles & Ownership Checks
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The permissions, roles and ownership checks work the same, but this time you can pass the team in the options array.

.. code-block:: php

    $options = [
        'team' => 'my-awesome-team',
        'requireAll' => false,
        'foreignKeyName' => 'writer_id'
    ];

    $post = Post::find(1);
    $user->canAndOwns(['edit-post', 'delete-post'], $post, $options);
    $user->hasRoleAndOwns(['admin', 'writer'], $post, $options);
