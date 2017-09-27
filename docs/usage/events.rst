Events
======

Laratrust comes with an events system that works like the Laravel `model events <https://laravel.com/docs/eloquent#events>`_. The events that you can listen to are **roleAttached**, **roleDetached**, **permissionAttached**, **permissionDetached**, **roleSynced**, **permissionSynced**.

.. NOTE::
    Inside the Role model only the **permissionAttached**, **permissionDetached** and **permissionSynced** events will be fired.

If you want to listen to a Laratrust event, inside your ``User`` or ``Role`` models put this:

.. code-block:: php


    <?php

    namespace App;

    use Laratrust\Traits\LaratrustUserTrait;

    class User extends Model
    {
       use LaratrustUserTrait;

       public static function boot() {
            parent::boot();

            static::roleAttached(function($user, $role, $team) {
            });
            static::roleSynced(function($user, $changes, $team) {
            });
       }
    }

.. NOTE::
    The ``$team`` parameter is optional and if you are not using teams, it will be set to null.

The eventing system also supports observable classes:

.. code-block:: php

    <?php

    namespace App\Observers;

    use App\User;

    class UserObserver
    {

        public function roleAttached(User $user, $role, $team)
        {
            //
        }

        public function roleSynced(User $user, $changes, $team)
        {
            //
        }
    }

To register an observer, use the laratrustObserve method on the model you wish to observe. You may register observers in the boot method of one of your service providers. In this example, we'll register the observer in the AppServiceProvider:

.. code-block:: php

    <?php

    namespace App\Providers;

    use App\User;
    use App\Observers\UserObserver;
    use Illuminate\Support\ServiceProvider;

    class AppServiceProvider extends ServiceProvider
    {

        public function boot()
        {
            User::laratrustObserve(UserObserver::class);
        }

        ...
    }

.. NOTE::
    Inside your observable classes you can have your normal model events methods alongside Laratrust's events methods.
