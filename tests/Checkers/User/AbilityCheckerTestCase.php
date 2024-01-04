<?php

declare(strict_types=1);

namespace Laratrust\Tests\Checkers\User;

use Laratrust\Tests\Enums\Permission as EnumsPermission;
use Laratrust\Tests\Enums\Role as EnumsRole;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Group;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;

abstract class AbilityCheckerTestCase extends LaratrustTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();

        $permissionA = Permission::create(['name' => 'permission_a']);
        $permissionB = Permission::create(['name' => 'permission_b']);
        $permissionC = Permission::create(['name' => 'permission_c']);

        $group = Group::create(['name' => 'group_a']);
        $roleA = Role::create(['name' => 'role_a']);
        $roleB = Role::create(['name' => 'role_b']);

        $roleA->givePermission($permissionA);
        $roleB->givePermissions([$permissionB, EnumsPermission::PERM_C]);

        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $group->addRole($roleB);
        $this->user->addRole($roleA)->addToGroup($group);
    }

    protected function abilityShouldReturnBooleanAssertions()
    {
        // Case: User has everything.
        $this->assertTrue(
            $this->user->ability(
                [EnumsRole::ROLE_A, 'role_b'],
                [EnumsPermission::PERM_A, 'permission_c']
            )
        );
        $this->assertTrue(
            $this->user->ability(
                ['role_a', 'role_b'],
                [EnumsPermission::PERM_A, 'permission_c'],
            )
        );
        $this->assertTrue(
            $this->user->ability(
                [EnumsRole::ROLE_A],
                ['permission_a'],
                ['validate_all' => true]
            )
        );

        // Case: User lacks a role.
        $this->assertTrue(
            $this->user->ability(
                ['Nonrole_a', 'role_b'],
                ['permission_a', 'permission_b']
            )
        );
        $this->assertFalse(
            $this->user->ability(
                ['Nonrole_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['validate_all' => true]
            )
        );

        // Case: User lacks a permission.
        $this->assertTrue(
            $this->user->ability(
                ['role_a', 'role_b'],
                ['user_cannot_a', 'permission_b']
            )
        );
        $this->assertFalse(
            $this->user->ability(
                ['role_a', 'role_b'],
                ['user_cannot_a', 'permission_b'],
                ['validate_all' => true]
            )
        );

        // Case: User lacks everything.
        $this->assertFalse(
            $this->user->ability(
                ['Nonrole_a', 'Nonrole_b'],
                ['user_cannot_a', 'user_cannot_b']
            )
        );
        $this->assertFalse(
            $this->user->ability(
                ['Nonrole_a', 'Nonrole_b'],
                ['user_cannot_a', 'user_cannot_b'],
                ['validate_all' => true]
            )
        );
    }

    protected function abilityShouldReturnArrayAssertions()
    {
        // Case: User has everything.
        $this->assertSame(
            [
                'roles' => ['role_a' => true, 'role_b' => true],
                'permissions' => ['permission_a' => true, 'permission_b' => true],
            ],
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles' => ['role_a' => true, 'role_b' => true],
                'permissions' => ['permission_a' => true, 'permission_b' => true],
            ],
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles' => ['role_a' => true],
                'permissions' => ['permission_a' => true],
            ],
            $this->user->ability(
                ['role_a'],
                ['permission_a'],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );
    }

    protected function abilityShouldReturnBothAssertions()
    {
        $this->assertSame(
            [
                true,
                [
                    'roles' => ['role_a' => true, 'role_b' => true],
                    'permissions' => ['permission_a' => true, 'permission_b' => true],
                ],
            ],
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                true,
                [
                    'roles' => ['role_a' => true, 'role_b' => true],
                    'permissions' => ['permission_a' => true, 'permission_b' => true],
                ],
            ],
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                true,
                [
                    'roles' => ['role_a' => true, 'role_b' => true],
                    'permissions' => ['permission_a' => true, 'permission_b' => true],
                ],
            ],
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );
    }

    protected function abilityShouldAcceptStringsAssertions()
    {
        $this->assertSame(
            $this->user->ability(
                ['role_a', 'Nonrole_b'],
                ['permission_a', 'user_cannot_b'],
                ['return_type' => 'both']
            ),
            $this->user->ability(
                'role_a|Nonrole_b',
                'permission_a|user_cannot_b',
                ['return_type' => 'both']
            )
        );

        $this->assertSame(
            $this->user->ability(
                ['role_a'],
                ['permission_a'],
                ['return_type' => 'both']
            ),
            $this->user->ability(
                'role_a',
                'permission_a',
                ['return_type' => 'both']
            )
        );
    }

    protected function abilityDefaultOptionsAssertions()
    {
        // Case: User has everything.
        $this->assertSame(
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b']
            ),
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );

        $this->assertSame(
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b']
            ),
            $this->user->ability(
                ['role_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );

        // Case: User lacks a role.
        $this->assertSame(
            $this->user->ability(
                ['Nonrole_a', 'role_b'],
                ['permission_a', 'permission_b']
            ),
            $this->user->ability(
                ['Nonrole_a', 'role_b'],
                ['permission_a', 'permission_b'],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );
    }

    protected function abilityShouldThrowInvalidArgumentExceptionAssertions()
    {
        $this->assertFalse($this->isExceptionThrown($this->user, ['RoleA'], ['manage_a'], ['return_type' => 'boolean']));
        $this->assertFalse($this->isExceptionThrown($this->user, ['RoleA'], ['manage_a'], ['return_type' => 'array']));
        $this->assertFalse($this->isExceptionThrown($this->user, ['RoleA'], ['manage_a'], ['return_type' => 'both']));
        $this->assertTrue($this->isExceptionThrown($this->user, ['RoleA'], ['manage_a'], ['return_type' => 'potato']));
        $this->assertTrue($this->isExceptionThrown($this->user, ['RoleA'], ['manage_a'], ['validate_all' => 'potato']));
    }

    /**
     * Check if an exception is thrown when checking the user ability
     */
    public function isExceptionThrown(User $user, array $roles, array $perms, array $options): bool
    {
        $isExceptionThrown = false;

        try {
            $user->ability($roles, $perms, $options);
        } catch (\InvalidArgumentException $e) {
            $isExceptionThrown = true;
        }

        return $isExceptionThrown;
    }
}
