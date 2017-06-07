.. _user-model:

User
====

Next, use the ``LaratrustUserTrait`` trait in your existing user models. For example:

.. code-block:: php

    <?php

    use Laratrust\Traits\LaratrustUserTrait;

    class User extends Model
    {
       use LaratrustUserTrait; // add this trait to your user model

       ...
    }

This will enable the relation with ``Role`` and ``Permission``, and add the following methods ``roles()``, ``hasRole($name)``, ``hasPermission($permission)``, ``isAbleTo($permission)``, ``can($permission)``, and ``ability($roles, $permissions, $options)`` within your ``User`` model.

.. NOTE::
    Optional: If you would like to use the hierarchical level functionality, then please add the additional Trait to your user model.

.. code-block:: php

    <?php

    use Laratrust\Traits\LaratrustUserTrait;
    use Laratrust\Traits\LaratrustHasLevelsTrait;

    class User extends Model
    {
       use LaratrustUserTrait, LaratrustHasLevelsTrait; // add this trait to your user model

       ...
    }

This optional functionality will add the following methods ``hasLevelOrGreater($level)``, ``hasLevelOrLess($level)``, ``hasLevelBetween('$level1^$level2')``

Do not forget to dump composer autoload::

    composer dump-autoload

.. IMPORTANT::
    At this point you are ready to go