<?php

namespace Laratrust\Tests\Models;

use Laratrust\Contracts\Ownable;

class OwnableObject implements Ownable
{
    public function ownerKey($owner)
    {
        return 1;
    }
}
