.. _teams-configuration:

Teams
=====

.. NOTE::
    The teams feature is **optional**, this part covers how to configure it after the installation.

    If you had your ``use_teams`` set to ``true`` during the installation and automatic setup, you can skip this part.

1. Set the ``use_teams`` value to ``true`` in your ``config/laratrust.php`` file.

2. Run ``php artisan laratrust:setup-teams``.

3. Run ``php artisan migrate`` to apply the changes to the database.

Now you can use the teams feature in you code.

Please refer to the :ref:`teams-concepts` concepts to learn how to use them.