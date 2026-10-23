<?php

declare(strict_types=1);

namespace Laratrust\Tests;

use Illuminate\Support\Facades\Blade;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;

class BladeDirectivesTest extends LaratrustTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $this->app['config']->set('laratrust.teams.enabled', true);

        $user = User::create(['name' => 'test', 'email' => 'test@test.com']);
        $team = Team::create(['name' => 'team_a']);
        $user->addRole(Role::create(['name' => 'editor']));
        $user->addRole(Role::create(['name' => 'owner']), $team);
        $user->givePermission(Permission::create(['name' => 'posts.edit']));

        $this->actingAs($user);
    }

    public function test_role_directive()
    {
        $this->assertSame('yes', $this->render("@role('editor') yes @endrole"));
        $this->assertSame('', $this->render("@role('admin') yes @endrole"));
        $this->assertSame('yes', $this->render("@role('owner', 'team_a') yes @endrole"));
        $this->assertSame('', $this->render("@role(['editor', 'admin'], requireAll: true) yes @endrole"));
    }

    public function test_role_directive_supports_else_elserole_and_unless()
    {
        $template = "@role('admin') admin @elserole('editor') editor @else nobody @endrole";

        $this->assertSame('editor', $this->render($template));
        $this->assertSame('not admin', $this->render("@unlessrole('admin') not admin @endrole"));
    }

    public function test_permission_directive()
    {
        $this->assertSame('yes', $this->render("@permission('posts.edit') yes @endpermission"));
        $this->assertSame('yes', $this->render("@permission('posts.*') yes @endpermission"));
        $this->assertSame('no', $this->render("@permission('posts.delete') yes @else no @endpermission"));
    }

    public function test_ability_directive()
    {
        $this->assertSame('yes', $this->render("@ability('admin', 'posts.edit') yes @endability"));
        $this->assertSame('', $this->render("@ability('admin', 'posts.edit', options: ['validate_all' => true]) yes @endability"));
    }

    public function test_directives_are_false_for_guests()
    {
        $this->app['auth']->logout();

        $this->assertSame('guest', $this->render("@role('editor') yes @else guest @endrole"));
    }

    protected function render(string $template): string
    {
        return trim(Blade::render($template));
    }
}
