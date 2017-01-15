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

It is possible to use pipe symbol as *OR* operator:

.. code-block:: php

    'middleware' => ['role:admin|root']

To emulate *AND* functionality just use multiple instances of middleware:

.. code-block:: php

    'middleware' => ['role:owner', 'role:writer']

For more complex situations use ``ability`` middleware which accepts 3 parameters: roles, permissions, validate_all:

.. code-block:: php

    'middleware' => ['ability:admin|owner,create-post|edit-user,true']

Middleware Return
^^^^^^^^^^^^^^^^^

The middleware supports two kinds of returns in case the check fails. You can configure the return type and the value in the ``config/laratrust.php`` file.

Abort
-----

By default the middleware aborts with a code ``403`` but you can customize it by changing the ``middleware_params`` value.

Redirect
--------

To make a redirection in case the middleware check fails, you will need to change the ``middleware_handling`` value to ``redirect`` and the ``middleware_params`` to the route you need to be redirected. Leaving the configuration like this:

.. code-block:: php

    'middleware_handling' => 'redirect',
    'middleware_params'   => '/home',       // Change this to the route you need