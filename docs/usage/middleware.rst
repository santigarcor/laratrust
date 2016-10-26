Middleware
==========

Concepts
^^^^^^^^

You can use a middleware to filter routes and route groups by permission or role:

.. code-block:: php

    Route::group(['prefix' => 'admin', 'middleware' => ['role:admin']], function() {
        Route::get('/', 'AdminController@welcome');
        Route::get('/manage', ['middleware' => ['permission:manage-admins'], 'uses' => 'AdminController@manageAdmins']);
    });

Roles
^^^^^
The main structure of the roles check is:

.. code-block:: php
    
    'middleware' => ['role:roles_split_by_pipe[,group_or_require_all,require_all]']

Examples:

.. code-block:: php
    
    'middleware' => ['role:admin|writer']                // This will check if the user has the role admin OR writer
    'middleware' => ['role:admin|writer,true']           // This will check if the user has the role admin AND writer
    'middleware' => ['role:admin|writer,my-group']       // This will check if the user has the role admin OR writer inside 'my-group'
    'middleware' => ['role:admin|writer,my-group,true']  // This will check if the user has the role admin AND writer inside 'my-group'

Permissions
^^^^^^^^^^^
The main structure of the permissions check is:

.. code-block:: php
    
    'middleware' => ['permission:permissions_split_by_pipe[,group_or_require_all,require_all]']

Examples:

.. code-block:: php
    
    'middleware' => ['permission:create-post|edit-user']                // This will check if the user can create-post OR edit-user
    'middleware' => ['permission:create-post|edit-user,true']           // This will check if the user can create-post AND edit-user
    'middleware' => ['permission:create-post|edit-user,my-group']       // This will check if the user can create-post OR edit-user inside 'my-group'
    'middleware' => ['permission:create-post|edit-user,my-group,true']  // This will check if the user can create-post AND edit-user inside 'my-group'

Ability
^^^^^^^
The main structure of the ability check is:

.. code-block:: php
    
    'middleware' => ['ability:roles_split_by_pipe,permissions_split_by_pipe[,group_or_require_all,require_all]']

Examples:

.. code-block:: php
    
    'middleware' => ['ability:admin,create-post']                 // This will check if the user can create-post OR has admin role
    'middleware' => ['ability:admin,create-post,true']            // This will check if the user can create-post AND has admin role
    'middleware' => ['ability:admin,create-post,my-group']        // This will check if the user can create-post OR has admin role inside my-group
    'middleware' => ['ability:admin,create-post,my-group,true']   // This will check if the user can create-post AND has admin role inside my-group

Middleware Return
^^^^^^^^^^^^^^^^^

The middleware supports two kinds of returns in case the check fails. You can configure the return type and the value in the ``config/laratrust.php`` file.

Abort
-----

By default the middleware aborts with a code ``403`` but you can customize it changing the ``middleware_params`` value.

Redirect
--------

To make a redirection in case the middleware check fails. You will need to change the ``middleware_handling`` value to ``redirect`` and the ``middleware_params`` to the route you need to be redirected. Leaving the configuration like this:

.. code-block:: php

    'middleware_handling' => 'redirect',
    'middleware_params'   => 'home',       // Change this to the route you need