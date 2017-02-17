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

Role Permissions Assignment & Removal
-------------------------------------
By using the ``LaratrustRoleTrait`` we can do the following:
   
Assignment
^^^^^^^^^^  

.. code-block:: php

   $admin->attachPermission($createPost);
   // equivalent to $admin->permissions()->attach([$createPost->id]);

   $owner->attachPermissions([$createPost, $editUser]);
   // equivalent to $owner->permissions()->attach([$createPost->id, $editUser->id]);

   $owner->syncPermissions([$createPost, $editUser]);
   // equivalent to $owner->permissions()->sync([$createPost->id, $editUser->id]);

Removal
^^^^^^^

.. code-block:: php

   $admin->detachPermission($createPost);
   // equivalent to $admin->permissions()->detach([$createPost->id]);

   $owner->detachPermissions([$createPost, $editUser]);
   // equivalent to $owner->permissions()->detach([$createPost->id, $editUser->id]);

User Roles Assignment & Removal
-------------------------------

With both roles created let's assign them to the users.
Thanks to the ``LaratrustUserTrait`` this is as easy as:

Assignment
^^^^^^^^^^  

.. code-block:: php

   $user->attachRole($admin); // parameter can be an Role object, array, or id
   // equivalent to $user->roles()->attach([$admin->id]);

   $user->attachRoles([$admin, $owner]); // parameter can be an Role object, array, or id
   // equivalent to $user->roles()->attach([$admin->id, $owner->id]);

   $user->syncRoles([$admin->id, $owner->id]);
   // equivalent to $user->roles()->sync([$admin->id]);

Removal
^^^^^^^

.. code-block:: php

   $user->detachRole($admin); // parameter can be an Role object, array, or id
   // equivalent to $user->roles()->detach([$admin->id]);

   $user->detachRoles([$admin, $owner]); // parameter can be an Role object, array, or id
   // equivalent to $user->roles()->detach([$admin->id, $owner->id]);

User Permissions Assignment & Removal
-------------------------------------

You can attach single permissions to an user, so in order to do it you only have to make:

Assignment
^^^^^^^^^^

.. code-block:: php

   $user->attachPermission($editUser); // parameter can be an Permission object, array, or id
   // equivalent to $user->permissions()->attach([$editUser->id]);

   $user->attachPermissions([$editUser, $createPost]); // parameter can be an Permission object, array, or id
   // equivalent to $user->permissions()->attach([$editUser->id, $createPost->id]);

   $user->syncPermissions([$editUser->id, $createPost->id]);
   // equivalent to $user->permissions()->sync([$editUser->id, createPost->id]);

Removal
^^^^^^^

.. code-block:: php

   $user->detachPermission($createPost); // parameter can be an Permission object, array, or id
   // equivalent to $user->roles()->detach([$createPost->id]);

   $user->detachPermissions([$createPost, $editUser]); // parameter can be an Permission object, array, or id
   // equivalent to $user->roles()->detach([$createPost->id, $editUser->id]);

Checking for Roles & Permissions
--------------------------------

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
------------

More advanced checking can be done using the awesome ``ability`` function.
It takes in three parameters (roles, permissions, options):
   
* ``roles`` is a set of roles to check.
* ``permissions`` is a set of permissions to check.
* ``options`` is a set of options to change the method behavior.

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

Objects's Ownership
-----------------

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

Permissions, Roles and Ownership Checks
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you want to check if an user can do something or has a role, and also is the owner of an object you can use the ``canAndOwns`` and ``hasRoleAndOwns`` methods:

Both methods accept three parameters:

* ``permission`` or ``role`` are the permission or role to check (This can be an array of roles or permissions).
* ``thing`` is the object used to check the ownership .
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

If the object ownership is witha a more complex logic you can implement the Ownable interface so you can use the ``owns``, ``canAndOwns`` and ``hasRoleAndOwns`` methods in these cases:

.. code-block:: php

   class SomeOwnedObject implements \Laratrust\Contracts\Ownable
   {
      ...

      public function ownerKey()
      {
         return $this->someRelationship->user->id;
      }

      ...
   }

.. NOTE::
   The ``ownerKey`` method **must** return the object's owner id value.

And then in your code you can simply do:

.. code-block:: php
   
   $user = User::find(1);
   $theObject = new SomeOwnedObject;
   $user->owns($theObject);            // This will return true or false depending of what the ownerKey method returns