# User

Next, use the `LaratrustUserTrait` trait in your existing `User` model. For example:

```php
<?php

use Laratrust\Traits\LaratrustUserTrait;

class User extends Model
{
    use LaratrustUserTrait; // add this trait to your user model

    ...
}
```

This will enable the relation with `Role` and add the following methods `roles()`, `hasRole($name)`, `can($permission)`, and `ability($roles, $permissions, $options)` within your `User` model.

Don't forget to dump composer autoload

```bash
composer dump-autoload
```

**At this point you are ready to go.**