# Installation

1. You can install the package using composer:
```bash
composer require santigarcor/laratrust
```

2. Publish the configuration file:

```bash
php artisan vendor:publish --tag="laratrust"
```
::: warning
If this command did not publish any files, chances are, the Laratrust service provider hasn't been registered. Try clearing your configuration cache
```bash
php artisan config:clear
```
:::

3. Run the setup command:

::: tip IMPORTANT
**Before running the command go to your* `config/laratrust.php` *file and change the values according to your needs.**
:::

```bash
php artisan laratrust:setup
```

This command will generate the migrations, create the `Role` and `Permission` models (if you are using the teams feature it will also create a `Team` model) and will add the trait to the configured user models.

4. Dump the autoloader:
```bash
composer dump-autoload
```

5. Run the migrations:
```bash
php artisan migrate
```

::: tip IMPORTANT
**If you did the steps above you are done with the configuration, if not, please read and follow the whole configuration process**
:::
