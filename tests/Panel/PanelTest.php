<?php

declare(strict_types=1);

namespace Laratrust\Tests\Panel;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Js;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\User;

class PanelTest extends LaratrustTestCase
{
    protected User $user;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // The panel uses the "web" middleware group, which encrypts the cookies.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->user = User::create(['name' => 'test', 'email' => 'test@test.com']);
    }

    public function test_the_pages_render(): void
    {
        $role = Role::create(['name' => 'role_a', 'display_name' => 'Role A']);
        $permission = Permission::create(['name' => 'permission_a', 'display_name' => 'Permission A']);
        $role->givePermission($permission);
        $this->user->addRole($role);

        $this->get('/laratrust/roles-assignment')->assertOk()->assertSee('test');
        $this->get('/laratrust/roles-assignment/'.$this->user->id.'/edit?model=users')->assertOk()->assertSee('Role A');
        $this->get('/laratrust/roles')->assertOk()->assertSee('role_a');
        $this->get('/laratrust/roles/create')->assertOk()->assertSee('Permission A');
        $this->get("/laratrust/roles/{$role->id}/edit")->assertOk()->assertSee('Permission A');
        $this->get('/laratrust/permissions')->assertOk()->assertSee('permission_a');
        $this->get('/laratrust/permissions/create')->assertOk();
        $this->get("/laratrust/permissions/{$permission->id}/edit")->assertOk()->assertSee('permission_a');
    }

    public function test_roles_are_created_updated_and_deleted(): void
    {
        $permission = Permission::create(['name' => 'permission_a']);

        $this->post('/laratrust/roles', ['name' => 'role_a', 'display_name' => 'Role A', 'permissions' => [$permission->id]])
            ->assertRedirect('/laratrust/roles');
        $role = Role::where('name', 'role_a')->firstOrFail();
        $this->assertTrue($role->hasPermission('permission_a'));

        $this->put("/laratrust/roles/{$role->id}", ['display_name' => 'Renamed', 'permissions' => []])
            ->assertRedirect('/laratrust/roles');
        $this->assertSame('Renamed', $role->refresh()->display_name);
        $this->assertFalse($role->hasPermission('permission_a'));

        $this->delete("/laratrust/roles/{$role->id}")->assertRedirect('/laratrust/roles');
        $this->assertNull(Role::find($role->id));
    }

    public function test_a_role_assigned_to_users_is_not_deleted(): void
    {
        $role = Role::create(['name' => 'role_a']);
        $this->user->addRole($role);

        $this->delete("/laratrust/roles/{$role->id}")
            ->assertRedirect('/laratrust/roles')
            ->assertSessionHas('laratrust-warning');
        $this->assertNotNull(Role::find($role->id));
    }

    public function test_the_role_restrictions_are_enforced(): void
    {
        $this->app['config']->set('laratrust.panel.roles_restrictions', [
            'not_editable' => ['locked'],
            'not_deletable' => ['locked'],
            'not_removable' => [],
        ]);
        $role = Role::create(['name' => 'locked', 'display_name' => 'Locked']);

        $this->get("/laratrust/roles/{$role->id}/edit")->assertRedirect()->assertSessionHas('laratrust-error');
        $this->put("/laratrust/roles/{$role->id}", ['display_name' => 'Changed'])->assertSessionHas('laratrust-error');
        $this->delete("/laratrust/roles/{$role->id}")->assertSessionHas('laratrust-error');
        $this->assertSame('Locked', $role->refresh()->display_name);
    }

    public function test_permissions_are_created_and_updated(): void
    {
        $this->post('/laratrust/permissions', ['name' => 'permission_a', 'display_name' => 'Permission A'])
            ->assertRedirect('/laratrust/permissions');
        $permission = Permission::where('name', 'permission_a')->firstOrFail();

        $this->put("/laratrust/permissions/{$permission->id}", ['display_name' => 'Renamed'])
            ->assertRedirect('/laratrust/permissions');
        $this->assertSame('Renamed', $permission->refresh()->display_name);
    }

    public function test_roles_and_permissions_are_assigned_to_users(): void
    {
        $role = Role::create(['name' => 'role_a']);
        $permission = Permission::create(['name' => 'permission_a']);

        $this->put('/laratrust/roles-assignment/'.$this->user->id.'?model=users', [
            'roles' => [$role->id],
            'permissions' => [$permission->id],
        ])->assertRedirect('/laratrust/roles-assignment?model=users');

        $this->assertTrue($this->user->hasRole('role_a'));
        $this->assertTrue($this->user->isAbleTo('permission_a'));
    }

    public function test_an_unknown_user_model_is_rejected(): void
    {
        $this->get('/laratrust/roles-assignment?model=unknown')->assertNotFound();
        $this->get('/laratrust/roles-assignment/'.$this->user->id.'/edit?model=unknown')
            ->assertRedirect('/laratrust/roles-assignment')
            ->assertSessionHas('laratrust-error');
    }

    public function test_the_assets_are_inlined_without_anything_to_publish(): void
    {
        $page = $this->get('/laratrust/roles')->assertOk()->getContent();

        $this->assertStringContainsString('<style>', $page);
        $this->assertStringContainsString('window.Alpine', $page);
        $this->assertStringNotContainsString('vendor/laratrust', $page);
    }

    public function test_the_inlined_assets_use_the_csp_nonce(): void
    {
        Vite::useCspNonce('the-nonce');

        $this->get('/laratrust/roles')->assertOk()->assertSeeHtml('<style nonce="the-nonce">')->assertSeeHtml('<script nonce="the-nonce">');
    }

    public function test_permissions_cant_be_created_when_disabled(): void
    {
        $this->app['config']->set('laratrust.panel.create_permissions', false);

        $this->get('/laratrust/permissions')->assertOk()->assertDontSee('New permission');
        $this->get('/laratrust/permissions/create')->assertNotFound();
        $this->post('/laratrust/permissions', ['name' => 'sneaky'])->assertNotFound();
        $this->assertFalse(Permission::where('name', 'sneaky')->exists());
    }

    public function test_a_role_that_cant_be_removed_is_kept_when_saving(): void
    {
        $this->app['config']->set('laratrust.panel.roles_restrictions.not_removable', ['locked']);
        $locked = Role::create(['name' => 'locked']);
        $other = Role::create(['name' => 'other']);
        $this->user->addRole($locked)->addRole($other);

        // The form submits the locked role through a hidden input, as its checkbox is disabled.
        $this->get('/laratrust/roles-assignment/'.$this->user->id.'/edit?model=users')->assertOk()->assertSeeHtml('<input type="hidden" name="roles[]" value="'.$locked->id.'">');

        $this->put('/laratrust/roles-assignment/'.$this->user->id.'?model=users', ['roles' => [$locked->id]]);

        $this->assertTrue($this->user->hasRole('locked'));
        $this->assertFalse($this->user->hasRole('other'));
    }

    public function test_values_reach_alpine_without_html_escaping(): void
    {
        $role = Role::create(['name' => 'owner', 'display_name' => "Owner's role"]);

        // @js() escapes for JavaScript ('), not for HTML (&#039;), which would show up in the input.
        $this->get("/laratrust/roles/{$role->id}/edit")->assertOk()->assertSeeHtml("laratrustNameFromDisplayName('owner', ".Js::from("Owner's role").', false)');
    }

    public function test_roles_that_cant_be_deleted_have_no_delete_action(): void
    {
        $this->app['config']->set('laratrust.panel.roles_restrictions.not_deletable', ['locked']);
        Role::create(['name' => 'locked']);

        $this->get('/laratrust/roles')
            ->assertOk()
            ->assertSeeHtml('title="This role can\'t be deleted"')
            ->assertDontSeeHtml('x-on:click="open(');
    }

    public function test_flash_messages_are_shown(): void
    {
        $this->post('/laratrust/permissions', ['name' => 'permission_a'])
            ->assertRedirect('/laratrust/permissions');

        $this->get('/laratrust/permissions')->assertSee('Permission created successfully');
    }
}
