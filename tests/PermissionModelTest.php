<?php

declare(strict_types=1);

namespace Laratrust\Test;

use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;

class PermissionModelTest extends LaratrustTestCase
{
    protected $permission;

    protected function setUp(): void
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
