<?php

declare(strict_types=1);

namespace Laratrust\Tests\Panel;

use Laratrust\Panel\RoleRestrictions;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Role;

class RoleRestrictionsTest extends LaratrustTestCase
{
    protected Role $superadmin;

    protected Role $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->superadmin = Role::create(['name' => 'superadmin']);
        $this->admin = Role::create(['name' => 'admin']);
    }

    public function test_role_is_editable(): void
    {
        $this->app['config']->set('laratrust.panel.roles_restrictions.not_editable', ['superadmin']);

        $this->assertFalse(RoleRestrictions::isEditable($this->superadmin));
        $this->assertFalse(RoleRestrictions::isEditable('superadmin'));
        $this->assertTrue(RoleRestrictions::isEditable($this->admin));
        $this->assertTrue(RoleRestrictions::isEditable('admin'));
    }

    public function test_role_is_deletable(): void
    {
        $this->app['config']->set('laratrust.panel.roles_restrictions.not_deletable', ['superadmin']);

        $this->assertFalse(RoleRestrictions::isDeletable($this->superadmin));
        $this->assertFalse(RoleRestrictions::isDeletable('superadmin'));
        $this->assertTrue(RoleRestrictions::isDeletable($this->admin));
        $this->assertTrue(RoleRestrictions::isDeletable('admin'));
    }

    public function test_role_is_removable(): void
    {
        $this->app['config']->set('laratrust.panel.roles_restrictions.not_removable', ['superadmin']);

        $this->assertFalse(RoleRestrictions::isRemovable($this->superadmin));
        $this->assertFalse(RoleRestrictions::isRemovable('superadmin'));
        $this->assertTrue(RoleRestrictions::isRemovable($this->admin));
        $this->assertTrue(RoleRestrictions::isRemovable('admin'));
    }

    public function test_no_restrictions_configured(): void
    {
        $this->app['config']->set('laratrust.panel.roles_restrictions', null);

        $this->assertTrue(RoleRestrictions::isEditable($this->superadmin));
        $this->assertTrue(RoleRestrictions::isDeletable($this->superadmin));
        $this->assertTrue(RoleRestrictions::isRemovable($this->superadmin));
    }
}
