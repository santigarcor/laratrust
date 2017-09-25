<?php

namespace Laratrust\Tests\Models;

class UserObserver
{
    public function roleAttached($user, $thing, $team)
    {
    }

    public function roleDetached($user, $thing, $team)
    {
    }

    public function permissionAttached($user, $thing, $team)
    {
    }

    public function permissionDetached($user, $thing, $team)
    {
    }

    public function roleSynced($user, $thing, $team)
    {
    }

    public function permissionSynced($user, $thing, $team)
    {
    }
}
