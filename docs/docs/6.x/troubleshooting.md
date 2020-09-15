# Troubleshooting
---
If you make changes directly to the Laratrust tables and when you run your code the changes are not reflected, please clear your application cache using::

```bash
php artisan cache:clear
```

Remember that Laratrust uses cache in the roles and permissions checks.

---

If you encounter an error when doing the migration that looks like::
```log
SQLSTATE[HY000]: General error: 1005 Can't create table 'laravelbootstrapstarter.#sql-42c_f8' (errno: 150)
    (SQL: alter table `role_user` add constraint role_user_user_id_foreign foreign key (`user_id`)
    references `users` (`id`)) (Bindings: array ())
```

Then it is likely that the `id` column in your user table does not match the `user_id` column in `role_user`.
Make sure both are `INT(10)`.

---

When trying to use the LaratrustUserTrait methods, you encounter the error which looks like::

    Class name must be a valid object or a string

Then probably you do not have published Laratrust assets or something went wrong when you did it.
First of all check that you have the `laratrust.php` file in your `app/config` directory.
If you don't, then try `php artisan vendor:publish` and, if it does not appear, manually copy the `/vendor/santigarcor/laratrust/src/config/config.php` file in your config directory and rename it `laratrust.php`.

---

erreur apres un php artisan db:seed sur laravel 8 

mttheophane@mttheophane-Latitude-E6540:~/programmations/personnel/apprendre/laratrust/auth$ composer dump-autoload
Cannot create cache directory /home/mttheophane/.composer/cache/repo/https---repo.packagist.org/, or directory is not writable. Proceeding without cache
Cannot create cache directory /home/mttheophane/.composer/cache/files/, or directory is not writable. Proceeding without cache
Cannot create cache directory /home/mttheophane/.composer/cache/repo/https---repo.packagist.org/, or directory is not writable. Proceeding without cache
Generating optimized autoload files
> Illuminate\Foundation\ComposerScripts::postAutoloadDump
> @php artisan package:discover --ansi
Discovered Package: facade/ignition
Discovered Package: fideloper/proxy
Discovered Package: fruitcake/laravel-cors
Discovered Package: laravel/tinker
Discovered Package: nesbot/carbon
Discovered Package: nunomaduro/collision
Discovered Package: santigarcor/laratrust
Package manifest generated successfully.
Generated optimized autoload files containing 4545 classes
mttheophane@mttheophane-Latitude-E6540:~/programmations/personnel/apprendre/laratrust/auth$ php artisan db:seed --class=LaratrustSeeder

   Illuminate\Contracts\Container\BindingResolutionException 

  Target class [Database\Seeders\LaratrustSeeder] does not exist.

  at vendor/laravel/framework/src/Illuminate/Container/Container.php:811
    807▕ 
    808▕         try {
    809▕             $reflector = new ReflectionClass($concrete);
    810▕         } catch (ReflectionException $e) {
  ➜ 811▕             throw new BindingResolutionException("Target class [$concrete] does not exist.", 0, $e);
    812▕         }
    813▕ 
    814▕         // If the type is not instantiable, the developer is attempting to resolve
    815▕         // an abstract type such as an Interface or Abstract Class and there is

      +24 vendor frames 
  25  artisan:37
      Illuminate\Foundation\Console\Kernel::handle(Object(Symfony\Component\Console\Input\ArgvInput), Object(Symfony\Component\Console\Output\ConsoleOutput))
mttheophane@mttheophane-Latitude-E6540:~/programmations/personnel/apprendre/laratrust/auth$ php artisan db:seed --class=LaratrustSeeder

   Illuminate\Contracts\Container\BindingResolutionException 
