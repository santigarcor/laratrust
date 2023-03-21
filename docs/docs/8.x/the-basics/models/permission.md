# Permission

```php
<?php

namespace App;

use Laratrust\Models\Permission as PermissionModel;

class Permission extends PermissionModel
{
}
```

The `Permission` model has the same three attributes as the `Role`:

- `name` — Unique name for the permission, used for looking up permission information in the application layer. For example: "create-post", "edit-user", "post-payment", "mailing-list-subscribe".
- `display_name` — Human readable name for the permission. Not necessarily unique and optional. For example "Create Posts", "Edit Users", "Post Payments", "Subscribe to mailing list".
- `description` — A more detailed explanation of the Permission.

In general, it may be helpful to think of the last two attributes in the form of a sentence: "The permission `display_name` allows a user to `description`."
