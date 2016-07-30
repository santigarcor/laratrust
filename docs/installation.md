# Installation
1) In order to install Laravel 5 Laratrust, just add the following to your composer.json. Then run `composer update`:

    "santigarcor/laratrust": "3.0.*"

or you can run the `composer require` command from your terminal:

    composer require "santigarcor/laratrust:3.0.*"

2) Then in your `config/app.php` add the following to the providers array:
```php
    Laratrust\LaratrustServiceProvider::class,
```
3) In the same `config/app.php` and add the following to the `aliases ` array:
```php
    'Laratrust'   => Laratrust\LaratrustFacade::class,
```

4) If you are going to use [Middleware](middleware.md) (requires Laravel 5.1 or later) you also need to add the following to `routeMiddleware` array in `app/Http/Kernel.php`.
```php
    'role' => \Laratrust\Middleware\LaratrustRole::class,
    'permission' => \Laratrust\Middleware\LaratrustPermission::class,
    'ability' => \Laratrust\Middleware\LaratrustAbility::class,
```
