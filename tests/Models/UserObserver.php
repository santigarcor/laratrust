<?php

declare(strict_types=1);

namespace Laratrust\Tests\Models;

class UserObserver
{
    public function roleAdded($user, $thing, $team)
    {
    }

    public function roleRemoved($user, $thing, $team)
    {
    }

    public function permissionAdded($user, $thing, $team)
    {
    }

    public function permissionRemoved($user, $thing, $team)
    {
    }

    public function roleSynced($user, $thing, $team)
    {
    }

    public function permissionSynced($user, $thing, $team)
    {
    }
}
