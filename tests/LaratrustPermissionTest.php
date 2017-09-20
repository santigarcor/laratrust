<?php

namespace Laratrust\Test;

use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;

class LaratrustPermissionTest extends LaratrustTestCase
{
    protected $permission;

    public function setUp()
    {
        parent::setUp();

        $this->migrate();
        $this->permission = new Permission();
    }

    public function testUsersRelationship()
    {
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\MorphToMany', $this->permission->users());
    }

    public function testAccessUsersRelationshipAsAttribute()
    {
        $this->assertEmpty($this->permission->users);
    }

    public function testRolesRelationship()
    {
        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Relations\BelongsToMany', $this->permission->roles());
    }
}
