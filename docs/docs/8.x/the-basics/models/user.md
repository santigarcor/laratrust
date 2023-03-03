# User

```php
<?php

use Laratrust\Traits\LaratrustUserTrait;

class User extends Model
{
    use LaratrustUserTrait; // add this trait to your user model

    ...
}
```

This class uses the `LaratrustUserTrait` to enable the relationships with `Role` and `Permission`.It also adds the following methods `roles()`, `hasRole($name)`, `hasPermission($permission)`, `isAbleTo($permission)`, `ability($roles, $permissions, $options)`, and `rolesTeams()` to the model.