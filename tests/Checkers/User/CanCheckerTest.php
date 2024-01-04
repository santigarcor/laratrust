<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers\User;

use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Group;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;

class CanCheckerTest extends LaratrustTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        Group::create(['name' => 'group_a']);
        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);

        $role = Role::create(['name' => 'role']);

        $role->givePermissions([$permissionA, $permissionB]);

        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $this->user->addRole($role);
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('laratrust.checker', 'default');
        $app['config']->set('laratrust.permissions_as_gates', true);
    }

    public function testCanShouldReturnBoolean()
    {
        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertTrue(
            $this->user->can(
                [EnumsPermission::PERM_A->value, 'permission_b']
            )
        );

        $this->assertFalse(
            $this->user->can(EnumsPermission::PERM_C->value)
        );

        // Case: User lacks a permission.
        if (method_exists($this->user, 'canAny')) {
            $this->assertTrue(
                $this->user->canAny(
                    ['user_cannot_a', 'permission_b']
                )
            );
        }
        $this->assertFalse(
            $this->user->can(
                ['user_cannot_a', 'permission_b']
            )
        );

        // Case: User lacks everything.
        $this->assertFalse(
            $this->user->can(
                ['user_cannot_a', 'user_cannot_b']
            )
        );
        if (method_exists($this->user, 'canAny')) {
            $this->assertFalse(
                $this->user->canAny(
                    ['user_cannot_a', 'user_cannot_b']
                )
            );
        }
    }
}
