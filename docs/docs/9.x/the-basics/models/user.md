# User

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laratrust\Contracts\LaratrustUser;
use Laratrust\Traits\HasRolesAndPermissions;

class User extends Authenticatable implements LaratrustUser
{
    use HasRolesAndPermissions; // add this trait to your user model

    // ...
}
```

The `HasRolesAndPermissions` trait adds the `roles()` and `permissions()` relationships with the `Role` and `Permission` models. It also adds the methods to check them, e.g. `hasRole()`, `hasPermission()`, `isAbleTo()` and `ability()`, the methods to add, remove and sync them, and the `rolesTeams()` and `permissionsTeams()` relationships.
