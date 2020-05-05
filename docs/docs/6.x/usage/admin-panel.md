# Administration Panel

Laratrust comes with a simple administration panel for roles, permissions and their assignment to the users.

Currently it supports:

1. Permissions CRUD.
2. Roles CRUD and permissions assignment.
3. Assignment of roles and permission to the multiple users defined in `user_models` in the `config/laratrust.php` file.

By default the URL to access the panel is `/laratrust`.

To customize the the URL and other available settings in the panel please go to the `panel` section in the `config/laratrust.php` file.