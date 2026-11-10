# Migrations

The migration will create five (or six if you use teams feature) tables in your database:

* `roles` — stores role records.
* `permissions` — stores permission records.
* `teams` — stores teams records (Only if you use the teams feature).
* `role_user` — stores [polymorphic](https://laravel.com/docs/eloquent-relationships#polymorphic-relations) relations between roles and users.
* `permission_role` — stores [many-to-many](https://laravel.com/docs/eloquent-relationships#many-to-many) relations between roles and permissions.
* `permission_user` — stores [polymorphic](https://laravel.com/docs/eloquent-relationships#polymorphic-relations) relations between users and permissions.
