Middleware
==========

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
