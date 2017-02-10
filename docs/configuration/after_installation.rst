After Installation
==================

Configuration Files
^^^^^^^^^^^^^^^^^^^

Set the proper values in the ``config/auth.php``. These values will be used by laratrust to refer to the user model.

You can also publish the configuration for this package to further customize table names and model namespaces.

To change the configuration of laratrust you can change the values inside the ``config/laratrust.php`` file.

Automatic setup (Recommended)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you want to let laratrust to setup by itselft, just run the following command::

    php artisan laratrust:setup

This command will generate the migrations, create the ``Role`` and ``Permission`` models and will add the trait to the ``User`` model.

.. NOTE::
    The user trait will be added to the Model configured in the ``auth.php`` file.

And then do not forget to run::

    composer dump-autoload

.. IMPORTANT::
    **If you did the steps above you are done with the configuration, if not, please read and follow the whole configuration process**
