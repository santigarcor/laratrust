# Soft Deleting

The default migration takes advantage of `onDelete('cascade')` clauses within the pivot tables to remove relations when a parent record is deleted. If for some reason you can not use cascading deletes in your database, the `Role` model and the `HasRolesAndPermissions` trait include event listeners to manually delete records in relevant pivot tables.

In the interest of not accidentally deleting data, the event listeners will **not** delete pivot data when a model that uses soft deleting is soft deleted, so it keeps its roles and permissions when it's restored. When you force delete it, the listeners delete its pivot data:

```php
$role = Role::findOrFail(1); // Pull back a given role

// Soft delete: the role keeps its users and permissions.
$role->delete();
$role->restore();

// Force delete: the role's users and permissions are removed from the pivot tables.
$role->forceDelete();
```

While a role is soft deleted, the checks ignore it: its users don't have it nor its permissions until it's restored.
