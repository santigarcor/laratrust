# Teams Feature

::: tip NOTE
The teams feature is **optional**, this part covers how to configure it after the installation.

If you had your `teams.enabled` value set to `true` during the installation and automatic setup, you can skip this part.
:::


1. Set the `teams.enabled` value to `true` in your `config/laratrust.php` file.

2. Run:
```bash
php artisan laratrust:setup-teams
```

3. Run:
```bash
php artisan migrate
```

Now you can use the teams feature in you code.

Please refer to the <docs-link to="/usage/teams.html">teams concepts</docs-link> concepts to learn how to use them.
