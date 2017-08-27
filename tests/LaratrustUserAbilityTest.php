<?php

use Laratrust\Permission;
use Laratrust\Role;
use Mockery as m;

class LaratrustUserAbilityTest extends UserTest
{
    protected $permissions;
    protected $roles;
    protected $user;

    public function setUp()
    {
        parent::setUp();

        $this->permissions['A'] = $this->mockPermission('user_can_a');
        $this->permissions['B'] = $this->mockPermission('user_can_b');
        $this->permissions['C'] = $this->mockPermission('user_can_c');

        $this->team = $this->mockTeam('TeamA');
        $this->roles['A'] = $this->mockRole('UserRoleA');
        $this->roles['B'] = $this->mockRole('UserRoleB', $this->team->id);

        $this->roles['A']->perms = [$this->permissions['A']];
        $this->roles['B']->perms = [$this->permissions['B'], $this->permissions['C']];

        $this->user = m::mock('HasRoleUser')->makePartial();
        $this->user->roles = [$this->roles['A'], $this->roles['B']];
        $this->user->id = 4;
        $this->user->primaryKey = 'id';
    }

    protected function hasRoleAndHasPermissionExpectations()
    {
        $this->user->shouldReceive('hasRole')->with('UserRoleA', null)->andReturn(true);
        $this->user->shouldReceive('hasRole')->with('UserRoleA', 'TeamA')->andReturn(false);
        $this->user->shouldReceive('hasRole')->with('UserRoleB', 'TeamA')->andReturn(true);
        $this->user->shouldReceive('hasRole')->with('UserRoleB', null)->andReturn(false);
        $this->user->shouldReceive('hasRole')
            ->with(m::anyOf('NonUserRoleA', 'NonUserRoleB'), m::anyOf('TeamA', null))
            ->andReturn(false);
        $this->user->shouldReceive('hasPermission')->with('user_can_a', null)->andReturn(true);
        $this->user->shouldReceive('hasPermission')->with('user_can_a', 'TeamA')->andReturn(false);
        $this->user->shouldReceive('hasPermission')
            ->with(m::anyOf('user_can_b', 'user_can_c'), 'TeamA')
            ->andReturn(true);
        $this->user->shouldReceive('hasPermission')
            ->with(m::anyOf('user_can_b', 'user_can_c'), null)
            ->andReturn(false);
        $this->user->shouldReceive('hasPermission')
            ->with(m::anyOf('user_cannot_a', 'user_cannot_b'), m::anyOf('TeamA', null))
            ->andReturn(false);
    }

    public function testAbilityShouldReturnBoolean()
    {
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->hasRoleAndHasPermissionExpectations();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertTrue(
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b']
            )
        );
        $this->assertTrue(
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                'TeamA'
            )
        );
        $this->assertTrue(
            $this->user->ability(
                ['UserRoleA'],
                ['user_can_a'],
                ['validate_all' => true]
            )
        );

        // Case: User lacks a role.
        $this->assertTrue(
            $this->user->ability(
                ['NonUserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b']
            )
        );
        $this->assertFalse(
            $this->user->ability(
                ['NonUserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                ['validate_all' => true]
            )
        );

        // Case: User lacks a permission.
        $this->assertTrue(
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_cannot_a', 'user_can_b']
            )
        );
        $this->assertFalse(
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_cannot_a', 'user_can_b'],
                ['validate_all' => true]
            )
        );

        // Case: User lacks everything.
        $this->assertFalse(
            $this->user->ability(
                ['NonUserRoleA', 'NonUserRoleB'],
                ['user_cannot_a', 'user_cannot_b']
            )
        );
        $this->assertFalse(
            $this->user->ability(
                ['NonUserRoleA', 'NonUserRoleB'],
                ['user_cannot_a', 'user_cannot_b'],
                ['validate_all' => true]
            )
        );
    }

    public function testAbilityShouldReturnArray()
    {
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->hasRoleAndHasPermissionExpectations();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertSame(
            [
                'roles'       => ['UserRoleA' => true, 'UserRoleB' => false],
                'permissions' => ['user_can_a' => true, 'user_can_b' => false]
            ],
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles'       => ['UserRoleA' => false, 'UserRoleB' => true],
                'permissions' => ['user_can_a' => false, 'user_can_b' => true]
            ],
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                'TeamA',
                ['validate_all' => true, 'return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles'       => ['UserRoleA' => true],
                'permissions' => ['user_can_a' => true]
            ],
            $this->user->ability(
                ['UserRoleA'],
                ['user_can_a'],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );
    }

    public function testAbilityShouldReturnBoth()
    {
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->hasRoleAndHasPermissionExpectations();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame(
            [
                true,
                [
                    'roles'       => ['UserRoleA' => true, 'UserRoleB' => false],
                    'permissions' => ['user_can_a' => true, 'user_can_b' => false]
                ]
            ],
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                true,
                [
                    'roles'       => ['UserRoleA' => false, 'UserRoleB' => true],
                    'permissions' => ['user_can_a' => false, 'user_can_b' => true]
                ]
            ],
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                'TeamA',
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                false,
                [
                    'roles'       => ['UserRoleA' => true, 'UserRoleB' => false],
                    'permissions' => ['user_can_a' => true, 'user_can_b' => false]
                ]
            ],
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );
    }

    public function testAbilityShouldAcceptStrings()
    {
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->hasRoleAndHasPermissionExpectations();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame(
            $this->user->ability(
                ['UserRoleA', 'NonUserRoleB'],
                ['user_can_a', 'user_cannot_b'],
                ['return_type' => 'both']
            ),
            $this->user->ability(
                'UserRoleA,NonUserRoleB',
                'user_can_a,user_cannot_b',
                ['return_type' => 'both']
            )
        );
    }

    public function testAbilityDefaultOptions()
    {
        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $this->hasRoleAndHasPermissionExpectations();

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertSame(
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b']
            ),
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );

        $this->assertSame(
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                'TeamA'
            ),
            $this->user->ability(
                ['UserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                'TeamA',
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );

        // Case: User lacks a role.
        $this->assertSame(
            $this->user->ability(
                ['NonUserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b']
            ),
            $this->user->ability(
                ['NonUserRoleA', 'UserRoleB'],
                ['user_can_a', 'user_can_b'],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );
    }

    public function testAbilityShouldThrowInvalidArgumentException()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('manage_a');

        $roleA = $this->mockRole('RoleA');
        $roleA->perms = [$permA];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA];
        $user->id = 4;
        $user->primaryKey = 'id';

        function isExceptionThrown(
            HasRoleUser $user,
            array $roles,
            array $perms,
            array $options
        ) {
            $isExceptionThrown = false;

            try {
                $user->ability($roles, $perms, $options);
            } catch (InvalidArgumentException $e) {
                $isExceptionThrown = true;
            }

            return $isExceptionThrown;
        }

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasRole')
            ->times(3);
        $user->shouldReceive('hasPermission')
            ->times(3);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertFalse(isExceptionThrown($user, ['RoleA'], ['manage_a'], ['return_type' => 'boolean']));
        $this->assertFalse(isExceptionThrown($user, ['RoleA'], ['manage_a'], ['return_type' => 'array']));
        $this->assertFalse(isExceptionThrown($user, ['RoleA'], ['manage_a'], ['return_type' => 'both']));
        $this->assertTrue(isExceptionThrown($user, ['RoleA'], ['manage_a'], ['return_type' => 'potato']));
        $this->assertTrue(isExceptionThrown($user, ['RoleA'], ['manage_a'], ['validate_all' => 'potato']));
    }
}
