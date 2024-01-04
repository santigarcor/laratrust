<?php

declare(strict_types=1);

namespace Laratrust\Test\Checkers\Group;

use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Group;

class QueryCheckerTest extends LaratrustTestCase
{
    protected $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->app['config']->set('laratrust.checker', 'query');

        $this->group = Group::create(['name' => 'group']);
    }

    public function testHasPermission()
    {
        $permA = Permission::create(['name' => 'permission_a']);
        $permB = Permission::create(['name' => 'permission_b']);

        $this->group->permissions()->attach([$permA->id, $permB->id]);

        $this->assertTrue($this->group->hasPermission(EnumsPermission::PERM_A));
        $this->assertTrue($this->group->hasPermission('permission_b'));
        $this->assertFalse($this->group->hasPermission('permission_c'));

        $this->assertTrue($this->group->hasPermission(['permission_a', 'permission_b']));
        $this->assertTrue($this->group->hasPermission(['permission_a', 'permission_c']));
        $this->assertFalse($this->group->hasPermission([EnumsPermission::PERM_A, 'permission_c'], true));
        $this->assertFalse($this->group->hasPermission(['permission_c', 'permission_d']));
    }
}
