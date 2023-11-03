# Administration Panel

Laratrust comes with a simple administration panel for roles, permissions and their assignment to the users.

Currently it supports:

1. Permissions CRUD.
2. Roles CRUD and permissions assignment.
3. Assignment of roles and permission to the multiple users defined in `user_models` in the `config/laratrust.php` file.
4. Restricting roles from being edited, deleted or removed.

## How to use it

1. Go to your `config/laratrust.php` file and change the `panel.register` value to `true`.
2. Publish the assets used by the panel by running:
```bash
php artisan vendor:publish --tag=laratrust-assets --force
```

By default the URL to access the panel is `/laratrust`.

To customize the URL and other available settings in the panel please go to the `panel` section in the `config/laratrust.php` file.

## How to customize the views

1. Publish the blade views used by the panel by running:
```bash
php artisan vendor:publish --tag=laratrust-views --force
```
2. Now you can change how the panel looks. The published files are located in `resources/views/vendor/laratrust/panel`.

## Screenshots

Here are some screenshots of the admin panel.
<div class="admin-panel-screenshots">
<img src="/multiple-users.png" alt="Edit role view">

<img src="/role-assign.png" alt="Edit role view">

<img src="/role-assign-user.png" alt="Edit role view">

<img src="/edit-role.png" alt="Edit role view">
</div>
