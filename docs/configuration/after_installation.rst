After Installation
==================

Configuration Files
^^^^^^^^^^^^^^^^^^^

Laratrust now allows mutliple user models, so in order to configure it correctly, you must change the values inside the ``config/laratrust.php`` file.

.. _multiple-user-models:

Multiple User Models
--------------------

Inside the ``config/laratrust.php`` file you will find an ``user_models`` array, it contains the information about the multiple user models and the name of the relationships inside the ``Role`` and ``Permission`` models. For example:

.. code-block:: php

    'user_models' => [
        'users' => 'App\User',
    ],

.. NOTE::
    The value of the ``key`` inside the ``key => value`` pair defines the name of the relationship inside the ``Role`` and ``Permission`` models.

It means that there is only one user model using Laratrust, and the relationship with the ``Role`` and ``Permission`` models is going to be called like this:

.. code-block:: php
    
    $role->users;
    $role->users();

.. NOTE::
    Inside the ``role_user`` and ``permission_user`` table the ``user_type`` column will be set with the user's fully qualified class name, as the `polymorphic <https://laravel.com/docs/eloquent-relationships#polymorphic-relations>`_ relations describe it in Laravel docs.

    So if you want to use the MorphMap feature just change the ``use_morph_map`` value to ``true`` inside Laratrust's configuration file.

Automatic setup (Recommended)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you want to let laratrust to setup by itself, just run the following command::

    php artisan laratrust:setup

This command will generate the migrations, create the ``Role`` and ``Permission`` models and will add the trait to the configured user models.

.. NOTE::
    The user trait will be added to the Model configured in the ``auth.php`` file.

And then do not forget to run::

    composer dump-autoload

.. NOTE::
    If you plan on using the hierarchical Levels functionality, you will need to add the optional trait to your user model that is outlined in the :ref:`user-model` Model part of the documentation.

.. IMPORTANT::
    **If you did the steps above you are done with the configuration, if not, please read and follow the whole configuration process**
