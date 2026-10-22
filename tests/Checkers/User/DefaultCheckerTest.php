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

    public function test_get_roles()
    {
        $this->getRolesAssertions();
    }

    public function test_has_role()
    {
        $this->hasRoleAssertions();
    }

    public function test_has_permission()
    {
        $this->hasPermissionAssertions();
    }

    public function test_has_permission_with_placeholder_support()
    {
        $this->hasPermissionWithPlaceholderSupportAssertions();
    }

    public function test_user_disable_the_roles_and_permissions_caching()
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
