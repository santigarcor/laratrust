# User

Next, use the `LaratrustUserTrait` trait in your existing user models. For example:

```php
<?php

use Laratrust\Traits\LaratrustUserTrait;

class User extends Model
{
    use LaratrustUserTrait; // add this trait to your user model

    ...
}
```

This will enable the relation with `Role` and `Permission`, and add the following methods `roles()`, `hasRole($name)`, `hasPermission($permission)`, `isAbleTo($permission)`, `can($permission)`, `ability($roles, $permissions, $options)`, and `rolesTeams()` within your `User` model.

Do not forget to dump composer autoload

```bash
    composer dump-autoload
```

::: tip IMPORTANT
At this point you are ready to go
:::
