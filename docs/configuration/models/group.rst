Group
=====

Create a Group model inside ``app/models/Group.php`` using the following example:

.. code-block:: php

    <?php namespace App;

    use Laratrust\LaratrustGroup;

    class Group extends LaratrustGroup
    {
    }

The ``Group`` model has three main attributes:

* ``name`` — Unique name for the Group, used for looking up group information in the application layer. For example: "developers-groups", "designers-team".
* ``display_name`` — Human readable name for the Group. Not necessarily unique and optional. For example: "Developers Group", "Company Designers"
* ``description`` — A more detailed explanation of what the Group does. Also optional.

Both ``display_name`` and ``description`` are optional; their fields are nullable in the database.

