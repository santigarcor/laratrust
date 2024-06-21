# User

```php
<?php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;

class User extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions; // add this trait to your user model

    ...
}
```

This class uses the `HasRolesAndPermissions` to enable the relationships with `Role` and `Permission`.It also adds the following methods `roles()`, `hasRole($name)`, `hasPermission($permission)`, `isAbleTo($permission)`, `ability($roles, $permissions, $options)`, and `rolesTeams()` to the model.
