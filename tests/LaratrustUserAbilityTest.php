<?php

use Laratrust\Contracts\LaratrustUserInterface;
use Laratrust\Traits\LaratrustUserTrait;
use Illuminate\Cache\ArrayStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Laratrust\Permission;
use Laratrust\Role;
use Mockery as m;

class LaratrustUserAbilityTest extends UserTest
{

    public function testAbilityShouldReturnBoolean()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $userPermNameA = 'user_can_a';
        $userPermNameB = 'user_can_b';
        $userPermNameC = 'user_can_c';
        $nonUserPermNameA = 'user_cannot_a';
        $nonUserPermNameB = 'user_cannot_b';
        $userRoleNameA = 'UserRoleA';
        $userRoleNameB = 'UserRoleB';
        $nonUserRoleNameA = 'NonUserRoleA';
        $nonUserRoleNameB = 'NonUserRoleB';

        $permA = $this->mockPermission($userPermNameA);
        $permB = $this->mockPermission($userPermNameB);
        $permC = $this->mockPermission($userPermNameC);

        $roleA = $this->mockRole($userRoleNameA);
        $roleB = $this->mockRole($userRoleNameB);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $user->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleNameA, $userRoleNameB), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleNameA, $nonUserRoleNameB), m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf($userPermNameA, $userPermNameB, $userPermNameC), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with(m::anyOf($nonUserPermNameA, $nonUserPermNameB), m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertTrue(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB]
            )
        );
        $this->assertTrue(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true]
            )
        );

        // Case: User lacks a role.
        $this->assertTrue(
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB]
            )
        );
        $this->assertFalse(
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true]
            )
        );

        // Case: User lacks a permission.
        $this->assertTrue(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB]
            )
        );
        $this->assertFalse(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['validate_all' => true]
            )
        );

        // Case: User lacks everything.
        $this->assertFalse(
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB]
            )
        );
        $this->assertFalse(
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['validate_all' => true]
            )
        );
    }

    public function testAbilityShouldReturnBooleanGroup()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $userPermNameA = 'user_can_a';
        $userPermNameB = 'user_can_b';
        $userPermNameC = 'user_can_c';
        $nonUserPermNameA = 'user_cannot_a';
        $nonUserPermNameB = 'user_cannot_b';
        $userRoleNameA = 'UserRoleA';
        $userRoleNameB = 'UserRoleB';
        $nonUserRoleNameA = 'NonUserRoleA';
        $nonUserRoleNameB = 'NonUserRoleB';

        $permA = $this->mockPermission($userPermNameA);
        $permB = $this->mockPermission($userPermNameB);
        $permC = $this->mockPermission($userPermNameC);

        $group = $this->mockGroup('GroupA');
        $roleA = $this->mockRole($userRoleNameA, $group->id);
        $roleB = $this->mockRole($userRoleNameB, $group->id);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        Config::shouldReceive('get')->with('laratrust.group')->andReturn($group)->times(24);
        Config::shouldReceive('get')->with('laratrust.group_foreign_key', 'group_id')->andReturn('group_id')->times(32);
        $group->shouldReceive('where')->with('name', 'GroupA')->times(24)->andReturn($group);
        $group->shouldReceive('first')->times(24)->andReturn($group);
        $group->shouldReceive('getKey')->times(24)->andReturn($group->id);

        $roleA->shouldReceive('cachedPermissions')->times(12)->andReturn($roleA->perms); 
        $roleB->shouldReceive('cachedPermissions')->times(8)->andReturn($roleB->perms); 
        Config::shouldReceive('get')->with('cache.ttl', 60)->times(48)->andReturn('1440'); 
        Cache::shouldReceive('remember')->times(48)->andReturn($user->roles);

        $user->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleNameA, $userRoleNameB), $group->name, m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleNameA, $nonUserRoleNameB), $group->name, m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf($userPermNameA, $userPermNameB, $userPermNameC), $group->name, m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with(m::anyOf($nonUserPermNameA, $nonUserPermNameB), $group->name, m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertTrue(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                $group->name
            )
        );
        $this->assertTrue(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                $group->name,
                ['validate_all' => true]
            )
        );

        // Case: User lacks a role.
        $this->assertTrue(
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                $group->name
            )
        );
        $this->assertFalse(
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                $group->name,
                ['validate_all' => true]
            )
        );

        // Case: User lacks a permission.
        $this->assertTrue(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                $group->name
            )
        );
        $this->assertFalse(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                $group->name,
                ['validate_all' => true]
            )
        );

        // Case: User lacks everything.
        $this->assertFalse(
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB]
            )
        );
        $this->assertFalse(
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['validate_all' => true]
            )
        );
    }

    public function testAbilityShouldReturnArray()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $userPermNameA = 'user_can_a';
        $userPermNameB = 'user_can_b';
        $userPermNameC = 'user_can_c';
        $nonUserPermNameA = 'user_cannot_a';
        $nonUserPermNameB = 'user_cannot_b';
        $userRoleNameA = 'UserRoleA';
        $userRoleNameB = 'UserRoleB';
        $nonUserRoleNameA = 'NonUserRoleA';
        $nonUserRoleNameB = 'NonUserRoleB';

        $permA = $this->mockPermission($userPermNameA);
        $permB = $this->mockPermission($userPermNameB);
        $permC = $this->mockPermission($userPermNameC);

        $roleA = $this->mockRole($userRoleNameA);
        $roleB = $this->mockRole($userRoleNameB);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';


        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $user->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleNameA, $userRoleNameB), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleNameA, $nonUserRoleNameB), m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf($userPermNameA, $userPermNameB, $userPermNameC), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with(m::anyOf($nonUserPermNameA, $nonUserPermNameB), m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertSame(
            [
                'roles'       => [$userRoleNameA => true, $userRoleNameB => true],
                'permissions' => [$userPermNameA => true, $userPermNameB => true]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles'       => [$userRoleNameA => true, $userRoleNameB => true],
                'permissions' => [$userPermNameA => true, $userPermNameB => true]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );


        // Case: User lacks a role.
        $this->assertSame(
            [
                'roles'       => [$nonUserRoleNameA => false, $userRoleNameB => true],
                'permissions' => [$userPermNameA    => true, $userPermNameB  => true]
            ],
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles'       => [$nonUserRoleNameA => false, $userRoleNameB => true],
                'permissions' => [$userPermNameA    => true, $userPermNameB  => true]
            ],
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );


        // Case: User lacks a permission.
        $this->assertSame(
            [
                'roles'       => [$userRoleNameA    => true, $userRoleNameB  => true],
                'permissions' => [$nonUserPermNameA => false, $userPermNameB => true]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles'       => [$userRoleNameA    => true, $userRoleNameB  => true],
                'permissions' => [$nonUserPermNameA => false, $userPermNameB => true]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );


        // Case: User lacks everything.
        $this->assertSame(
            [
                'roles'       => [$nonUserRoleNameA => false, $nonUserRoleNameB => false],
                'permissions' => [$nonUserPermNameA => false, $nonUserPermNameB => false]
            ],
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['return_type' => 'array']
            )
        );
        $this->assertSame(
            [
                'roles'       => [$nonUserRoleNameA => false, $nonUserRoleNameB => false],
                'permissions' => [$nonUserPermNameA => false, $nonUserPermNameB => false]
            ],
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['validate_all' => true, 'return_type' => 'array']
            )
        );
    }

    public function testAbilityShouldReturnBoth()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $userPermNameA = 'user_can_a';
        $userPermNameB = 'user_can_b';
        $userPermNameC = 'user_can_c';
        $nonUserPermNameA = 'user_cannot_a';
        $nonUserPermNameB = 'user_cannot_b';
        $userRoleNameA = 'UserRoleA';
        $userRoleNameB = 'UserRoleB';
        $nonUserRoleNameA = 'NonUserRoleA';
        $nonUserRoleNameB = 'NonUserRoleB';

        $permA = $this->mockPermission($userPermNameA);
        $permB = $this->mockPermission($userPermNameB);
        $permC = $this->mockPermission($userPermNameC);

        $roleA = $this->mockRole($userRoleNameA);
        $roleB = $this->mockRole($userRoleNameB);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleNameA, $userRoleNameB), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleNameA, $nonUserRoleNameB), m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf($userPermNameA, $userPermNameB, $userPermNameC), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with(m::anyOf($nonUserPermNameA, $nonUserPermNameB), m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertSame(
            [
                true,
                [
                    'roles'       => [$userRoleNameA => true, $userRoleNameB => true],
                    'permissions' => [$userPermNameA => true, $userPermNameB => true]
                ]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                true,
                [
                    'roles'       => [$userRoleNameA => true, $userRoleNameB => true],
                    'permissions' => [$userPermNameA => true, $userPermNameB => true]
                ]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );


        // Case: User lacks a role.
        $this->assertSame(
            [
                true,
                [
                    'roles'       => [$nonUserRoleNameA => false, $userRoleNameB => true],
                    'permissions' => [$userPermNameA    => true, $userPermNameB  => true]
                ]
            ],
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                false,
                [
                    'roles'       => [$nonUserRoleNameA => false, $userRoleNameB => true],
                    'permissions' => [$userPermNameA    => true, $userPermNameB  => true]
                ]
            ],
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );


        // Case: User lacks a permission.
        $this->assertSame(
            [
                true,
                [
                    'roles'       => [$userRoleNameA    => true, $userRoleNameB  => true],
                    'permissions' => [$nonUserPermNameA => false, $userPermNameB => true]
                ]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                false,
                [
                    'roles'       => [$userRoleNameA    => true, $userRoleNameB  => true],
                    'permissions' => [$nonUserPermNameA => false, $userPermNameB => true]
                ]
            ],
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );


        // Case: User lacks everything.
        $this->assertSame(
            [
                false,
                [
                    'roles'       => [$nonUserRoleNameA => false, $nonUserRoleNameB => false],
                    'permissions' => [$nonUserPermNameA => false, $nonUserPermNameB => false]
                ]
            ],
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['return_type' => 'both']
            )
        );
        $this->assertSame(
            [
                false,
                [
                    'roles'       => [$nonUserRoleNameA => false, $nonUserRoleNameB => false],
                    'permissions' => [$nonUserPermNameA => false, $nonUserPermNameB => false]
                ]
            ],
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
                ['validate_all' => true, 'return_type' => 'both']
            )
        );
    }

    public function testAbilityShouldAcceptStrings()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        $permA = $this->mockPermission('user_can_a');
        $permB = $this->mockPermission('user_can_b');
        $permC = $this->mockPermission('user_can_c');

        $roleA = $this->mockRole('UserRoleA');
        $roleB = $this->mockRole('UserRoleB');

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $user->shouldReceive('hasRole')
            ->with(m::anyOf('UserRoleA', 'UserRoleB'), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with('NonUserRoleB', m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf('user_can_a', 'user_can_b', 'user_can_c'), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with('user_cannot_b', m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertSame(
            $user->ability(
                ['UserRoleA', 'NonUserRoleB'],
                ['user_can_a', 'user_cannot_b'],
                ['return_type' => 'both']
            ),
            $user->ability(
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
        | Set
        |------------------------------------------------------------
        */
        $userPermNameA = 'user_can_a';
        $userPermNameB = 'user_can_b';
        $userPermNameC = 'user_can_c';
        $nonUserPermNameA = 'user_cannot_a';
        $nonUserPermNameB = 'user_cannot_b';
        $userRoleNameA = 'UserRoleA';
        $userRoleNameB = 'UserRoleB';
        $nonUserRoleNameA = 'NonUserRoleA';
        $nonUserRoleNameB = 'NonUserRoleB';

        $permA = $this->mockPermission($userPermNameA);
        $permB = $this->mockPermission($userPermNameB);
        $permC = $this->mockPermission($userPermNameC);

        $roleA = $this->mockRole($userRoleNameA);
        $roleB = $this->mockRole($userRoleNameB);

        $roleA->perms = [$permA];
        $roleB->perms = [$permB, $permC];

        $user = m::mock('HasRoleUser')->makePartial();
        $user->roles = [$roleA, $roleB];
        $user->id = 4;
        $user->primaryKey = 'id';

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        $user->shouldReceive('hasRole')
            ->with(m::anyOf($userRoleNameA, $userRoleNameB), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('hasRole')
            ->with(m::anyOf($nonUserRoleNameA, $nonUserRoleNameB), m::anyOf(true, false))
            ->andReturn(false);
        $user->shouldReceive('can')
            ->with(m::anyOf($userPermNameA, $userPermNameB, $userPermNameC), m::anyOf(true, false))
            ->andReturn(true);
        $user->shouldReceive('can')
            ->with(m::anyOf($nonUserPermNameA, $nonUserPermNameB), m::anyOf(true, false))
            ->andReturn(false);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        // Case: User has everything.
        $this->assertSame(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB]
            ),
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );


        // Case: User lacks a role.
        $this->assertSame(
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB]
            ),
            $user->ability(
                [$nonUserRoleNameA, $userRoleNameB],
                [$userPermNameA, $userPermNameB],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );


        // Case: User lacks a permission.
        $this->assertSame(
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB]
            ),
            $user->ability(
                [$userRoleNameA, $userRoleNameB],
                [$nonUserPermNameA, $userPermNameB],
                ['validate_all' => false, 'return_type' => 'boolean']
            )
        );


        // Case: User lacks everything.
        $this->assertSame(
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB]
            ),
            $user->ability(
                [$nonUserRoleNameA, $nonUserRoleNameB],
                [$nonUserPermNameA, $nonUserPermNameB],
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
        $user->shouldReceive('can')
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
    }
}

class HasRoleUser extends Model implements LaratrustUserInterface
{
    use LaratrustUserTrait;

    public $roles;
    public $permissions;
    public $primaryKey;

    public function __construct() {
        $this->primaryKey = 'id';
        $this->setAttribute('id', 4);
    }

    public function getKey()
    {
        return $this->id;
    }

    public function belongsToMany($related, $table = NULL, $foreignKey = NULL, $otherKey = NULL, $relation = NULL)
    {

    }
}