<?php

namespace Laratrust\Test;

use Laratrust\Helper;
use Laratrust\Tests\Models\Role;
use Illuminate\Support\Facades\Config;
use Laratrust\Tests\LaratrustTestCase;

class LaratrustHelperTest extends LaratrustTestCase
{
    protected $superadmin;
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->superadmin = Role::create(['name' => 'superadmin']);
        $this->admin = Role::create(['name' => 'admin']);
    }

    public function testIfRoleIsEditable()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Config::set('laratrust.panel.roles_restrictions.not_editable', ['superadmin']);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertFalse(Helper::roleIsEditable($this->superadmin));
        $this->assertFalse(Helper::roleIsEditable($this->superadmin->name));
        $this->assertTrue(Helper::roleIsEditable($this->admin));
        $this->assertTrue(Helper::roleIsEditable($this->admin->name));
    }

    public function testRoleIsDeletable()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */
        Config::set('laratrust.panel.roles_restrictions.not_deletable', ['superadmin']);

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */
        $this->assertFalse(Helper::roleIsDeletable($this->superadmin));
        $this->assertFalse(Helper::roleIsDeletable($this->superadmin->name));
        $this->assertTrue(Helper::roleIsDeletable($this->admin));
        $this->assertTrue(Helper::roleIsDeletable($this->admin->name));
    }
}
