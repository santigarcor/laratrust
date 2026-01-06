<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers\User;

use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;

class DefaultCheckerTest extends CheckerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('laratrust.checker', 'default');
    }

    public function testGetRoles()
    {
        $this->getRolesAssertions();
    }

    public function testHasRole()
    {
        $this->hasRoleAssertions();
    }

    public function testHasPermission()
    {
        $this->hasPermissionAssertions();
    }

    public function testHasPermissionWithPlaceholderSupport()
    {
        $this->hasPermissionWithPlaceholderSupportAssertions();
    }

    public function testUserDisableTheRolesAndPermissionsCaching()
    {
        $this->userDisableTheRolesAndPermissionsCachingAssertions();
    }

    public function test_relationship_is_unset_when_a_role_or_permission_is_modified(): void
    {
        Role::query()->create([
            'name' => 'test',
            'display_name' => 'test',
            'description' => 'test',
        ]);
        Permission::query()->create([
            'name' => 'test_permission',
            'display_name' => 'test_permission',
            'description' => 'test_permission',
        ]);

        $this->user->roles;
        $this->user->permissions;
        $this->user->syncRoles(['test']);
        $this->user->syncPermissions(['test_permission']);
        $this->assertTrue($this->user->hasRole('test'));
        $this->assertTrue($this->user->hasPermission('test_permission'));
    }
}
