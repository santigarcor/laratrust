<?php

declare(strict_types=1);

namespace Laratrust\Tests\Middleware;

use Illuminate\Support\Facades\Route;
use Laratrust\Middleware\Ability;
use Laratrust\Middleware\Permission as PermissionMiddleware;
use Laratrust\Middleware\Role as RoleMiddleware;
use Laratrust\Tests\Enums\Role as EnumsRole;
use Laratrust\Tests\LaratrustTestCase;
use Laratrust\Tests\Models\Permission;
use Laratrust\Tests\Models\Role;
use Laratrust\Tests\Models\Team;
use Laratrust\Tests\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Real requests through the registered middleware, guards and routes.
 */
class MiddlewareRequestsTest extends LaratrustTestCase
{
    protected User $editor;

    protected User $viewer;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'users']);
        $app['config']->set('laratrust.teams.enabled', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrate();
        $team = Team::create(['name' => 'team_a']);
        $editorRole = Role::create(['name' => 'editor']);
        $editorRole->givePermission(Permission::create(['name' => 'posts.edit']));

        $this->editor = User::create(['name' => 'editor', 'email' => 'editor@test.com']);
        $this->editor->addRole($editorRole);
        $this->editor->addRole(Role::create(['name' => 'owner']), $team);
        $this->viewer = User::create(['name' => 'viewer', 'email' => 'viewer@test.com']);

        Route::middleware('permission:posts.edit,guard:web|admin')->get('/two-guards', fn (): string => 'ok');
        Route::middleware('permission:posts.edit,guard:web,admin')->get('/two-guards-commas', fn (): string => 'ok');
        Route::middleware('permission:posts.edit,guard:web|guard:admin')->get('/two-guards-8x', fn (): string => 'ok');
        Route::middleware('role:editor')->get('/editor', fn (): string => 'ok');
    }

    public function test_the_first_authenticated_guard_is_checked(): void
    {
        // Only the second guard is authenticated: its user is checked.
        $this->actingAs($this->editor, 'admin')->get('/two-guards')->assertOk();
    }

    public function test_later_guards_are_ignored_once_one_is_authenticated(): void
    {
        // Like Laravel's "auth" middleware: the first authenticated guard wins, even if a later one would pass.
        $this->actingAs($this->viewer, 'web')
            ->actingAs($this->editor, 'admin')
            ->get('/two-guards')
            ->assertForbidden();
    }

    public function test_the_8x_form_and_commas_also_work(): void
    {
        $this->actingAs($this->editor, 'admin')->get('/two-guards-8x')->assertOk();
        $this->actingAs($this->editor, 'admin')->get('/two-guards-commas')->assertOk();
    }

    public function test_no_authenticated_guard_is_unauthorized(): void
    {
        $this->get('/two-guards')->assertForbidden();
    }

    public function test_the_abort_message_is_translated(): void
    {
        $this->app['config']->set('laratrust.middleware.handlers.abort.message', 'laratrust.unauthorized');
        $this->app['translator']->addLines(['laratrust.unauthorized' => 'No tienes acceso.'], 'es');
        $this->app->setLocale('es');

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->viewer)->get('/editor');
            $this->fail('Expected the request to be aborted.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame('No tienes acceso.', $exception->getMessage());
        }
    }

    public function test_the_redirect_message_is_translated(): void
    {
        $this->app['config']->set('laratrust.middleware.handling', 'redirect');
        $this->app['config']->set('laratrust.middleware.handlers.redirect.message', [
            'key'     => 'error',
            'content' => 'laratrust.unauthorized',
        ]);
        $this->app['translator']->addLines(['laratrust.unauthorized' => 'No tienes acceso.'], 'es');
        $this->app->setLocale('es');

        $this->actingAs($this->viewer)
            ->get('/editor')
            ->assertRedirect('/home')
            ->assertSessionHas('error', 'No tienes acceso.');
    }

    public function test_untranslated_messages_are_kept_as_they_are(): void
    {
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->viewer)->get('/editor');
            $this->fail('Expected the request to be aborted.');
        } catch (HttpException $exception) {
            $this->assertSame('User does not have any of the necessary access rights.', $exception->getMessage());
        }
    }

    public function test_using_builds_the_middleware_strings(): void
    {
        $team = Team::where('name', 'team_a')->first();

        $this->assertSame(RoleMiddleware::class.':editor', RoleMiddleware::using('editor'));
        $this->assertSame(RoleMiddleware::class.':role_a|owner', RoleMiddleware::using([EnumsRole::ROLE_A, 'owner']));
        $this->assertSame(
            RoleMiddleware::class.":owner,{$team->id},require_all,guard:api",
            RoleMiddleware::using('owner', team: $team, requireAll: true, guards: 'api'),
        );
        $this->assertSame(
            PermissionMiddleware::class.':posts.edit,guard:web|admin',
            PermissionMiddleware::using('posts.edit', guards: ['web', 'admin']),
        );
        $this->assertSame(
            Ability::class.':editor,posts.edit,team_a,require_all',
            Ability::using('editor', 'posts.edit', team: 'team_a', validateAll: true),
        );
    }

    public function test_the_using_strings_work_as_route_middleware(): void
    {
        Route::middleware(RoleMiddleware::using('owner', team: 'team_a'))->get('/owner', fn (): string => 'ok');
        Route::middleware(PermissionMiddleware::using('posts.edit', guards: ['web', 'admin']))->get('/edit', fn (): string => 'ok');
        Route::middleware(Ability::using(['admin', 'editor'], 'posts.edit', validateAll: true))->get('/ability', fn (): string => 'ok');

        $this->actingAs($this->editor)->get('/owner')->assertOk();
        $this->actingAs($this->editor, 'admin')->get('/edit')->assertOk();
        $this->actingAs($this->editor)->get('/ability')->assertForbidden();
        $this->actingAs($this->viewer)->get('/owner')->assertForbidden();
    }

    /**
     * @return array<string, array{list<string>, array{team: string|null, require_all: bool, guards: array<int, string|null>}}>
     */
    public static function parameters(): array
    {
        return [
            'nothing'                          => [[], ['team' => null, 'require_all' => false, 'guards' => ['web']]],
            'team'                             => [['team_a'], ['team' => 'team_a', 'require_all' => false, 'guards' => ['web']]],
            'guards'                           => [['guard:web|admin'], ['team' => null, 'require_all' => false, 'guards' => ['web', 'admin']]],
            'team, require all, guards'        => [['team_a', 'require_all', 'guard:web|admin'], ['team' => 'team_a', 'require_all' => true, 'guards' => ['web', 'admin']]],
            'guards with commas'               => [['guard:web', 'admin'], ['team' => null, 'require_all' => false, 'guards' => ['web', 'admin']]],
            '8.x: everything in one parameter' => [['team_a', 'require_all|guard:web|admin'], ['team' => 'team_a', 'require_all' => true, 'guards' => ['web', 'admin']]],
            'guards, then require all'         => [['guard:web', 'admin', 'require_all'], ['team' => null, 'require_all' => true, 'guards' => ['web', 'admin']]],
            '8.x: options in the team slot'    => [['require_all|guard:api'], ['team' => null, 'require_all' => true, 'guards' => ['api']]],
            '8.x: team and options'            => [['team_a', 'guard:api|require_all'], ['team' => 'team_a', 'require_all' => true, 'guards' => ['api']]],
            '8.x: pipe-separated guards'       => [['guard:web|guard:admin'], ['team' => null, 'require_all' => false, 'guards' => ['web', 'admin']]],
        ];
    }

    /**
     * @param list<string>                                                                 $parameters
     * @param array{team: string|null, require_all: bool, guards: array<int, string|null>} $expected
     */
    #[DataProvider('parameters')]
    public function test_the_parameters_are_parsed(array $parameters, array $expected): void
    {
        $this->assertSame($expected, $this->parse($parameters));
    }

    public function test_parameters_that_dont_fit_throw_instead_of_being_ignored(): void
    {
        foreach ([['team_a', 'team_b'], ['require_all', 'team_a'], ['guard:web', 'require_all', 'admin']] as $parameters) {
            try {
                $this->parse($parameters);
                $this->fail('Expected an InvalidArgumentException for '.implode(',', $parameters));
            } catch (\InvalidArgumentException $exception) {
                $this->assertStringContainsString('The team must come before the options', $exception->getMessage());
            }
        }
    }

    /**
     * @param list<string> $parameters
     *
     * @return array{team: string|null, require_all: bool, guards: array<int, string|null>}
     */
    protected function parse(array $parameters): array
    {
        return (new class() extends RoleMiddleware {
            /**
             * @param list<string> $parameters
             *
             * @return array{team: string|null, require_all: bool, guards: array<int, string|null>}
             */
            public function parse(array $parameters): array
            {
                return $this->getValuesFromParameters($parameters);
            }
        })->parse($parameters);
    }
}
