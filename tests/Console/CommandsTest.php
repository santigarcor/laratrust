<?php

declare(strict_types=1);

namespace Laratrust\Tests\Console;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laratrust\Tests\LaratrustTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

class CommandsTest extends LaratrustTestCase
{
    protected string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/laratrust-commands-'.uniqid();
        File::ensureDirectoryExists($this->directory.'/app');
        File::ensureDirectoryExists($this->directory.'/database');
        $this->app->useAppPath($this->directory.'/app');
        $this->app->useDatabasePath($this->directory.'/database');
        // The generators read it from the app's composer.json, which the temporary directory doesn't have.
        (new ReflectionProperty($this->app, 'namespace'))->setValue($this->app, 'App\\');

        $this->app['config']->set('laratrust.user_models', ['users' => 'App\Models\User']);
        $this->app['config']->set('laratrust.models', [
            'role'       => 'App\Models\Role',
            'permission' => 'App\Models\Permission',
            'team'       => 'App\Models\Team',
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function commands(): array
    {
        return array_map(fn (string $name): array => [$name], [
            'laratrust:migration'   => 'laratrust:migration',
            'laratrust:permission'  => 'laratrust:permission',
            'laratrust:role'        => 'laratrust:role',
            'laratrust:seeder'      => 'laratrust:seeder',
            'laratrust:setup'       => 'laratrust:setup',
            'laratrust:setup-teams' => 'laratrust:setup-teams',
            'laratrust:team'        => 'laratrust:team',
        ]);
    }

    #[DataProvider('commands')]
    public function test_the_command_is_registered_with_a_description(string $name): void
    {
        $this->assertArrayHasKey($name, Artisan::all());
        $this->assertNotSame('', Artisan::all()[$name]->getDescription());
    }

    public function test_the_seeder_is_created_with_the_configured_models_and_tables(): void
    {
        $this->app['config']->set('laratrust.tables.role_user', 'custom_role_user');

        $this->artisan('laratrust:seeder')
            ->expectsOutputToContain('Seeder successfully created!')
            ->assertSuccessful();

        $seeder = File::get($this->directory.'/database/seeders/LaratrustSeeder.php');

        $this->assertValidPhp($seeder);
        $this->assertStringContainsString('namespace Database\Seeders;', $seeder);
        $this->assertStringContainsString('\App\Models\Role::firstOrCreate(', $seeder);
        $this->assertStringContainsString('\App\Models\Permission::firstOrCreate(', $seeder);
        $this->assertStringContainsString('\App\Models\User::create(', $seeder);
        $this->assertStringContainsString("DB::table('custom_role_user')->truncate();", $seeder);
        $this->assertStringNotContainsString('{{', $seeder);
    }

    public function test_an_existing_seeder_is_not_overwritten(): void
    {
        File::ensureDirectoryExists($this->directory.'/database/seeders');
        File::put($this->directory.'/database/seeders/LaratrustSeeder.php', 'mine');

        $this->artisan('laratrust:seeder')
            ->expectsOutputToContain('already exists')
            ->assertSuccessful();

        $this->assertSame('mine', File::get($this->directory.'/database/seeders/LaratrustSeeder.php'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function modelCommands(): array
    {
        return [
            'role'       => ['laratrust:role', 'Role'],
            'permission' => ['laratrust:permission', 'Permission'],
            'team'       => ['laratrust:team', 'Team'],
        ];
    }

    #[DataProvider('modelCommands')]
    public function test_the_model_is_created_in_the_configured_class(string $command, string $class): void
    {
        $this->artisan($command)->assertSuccessful();

        $model = File::get($this->directory."/app/Models/{$class}.php");

        $this->assertValidPhp($model);
        $this->assertStringContainsString('namespace App\Models;', $model);
        $this->assertStringContainsString("class {$class} extends", $model);
    }

    public function test_the_migration_is_created(): void
    {
        File::ensureDirectoryExists($this->directory.'/database/migrations');

        $this->artisan('laratrust:migration')
            ->expectsConfirmation('Proceed with the migration creation?', 'yes')
            ->assertSuccessful();

        $migrations = File::glob($this->directory.'/database/migrations/*_laratrust_setup_tables.php');

        $this->assertCount(1, $migrations);
        $this->assertValidPhp(File::get($migrations[0]));
    }

    public function test_the_migration_with_teams_runs_and_rolls_back(): void
    {
        $this->app['config']->set('laratrust.teams.enabled', true);
        File::ensureDirectoryExists($this->directory.'/database/migrations');
        $this->artisan('laratrust:migration')
            ->expectsConfirmation('Proceed with the migration creation?', 'yes')
            ->assertSuccessful();

        $migration = $this->requireMigration('laratrust_setup_tables');
        $migration->up();

        $this->assertTrue(Schema::hasTable('teams'));
        $this->assertTrue(Schema::hasColumn('role_user', 'team_id'));
        $this->assertTrue(Schema::hasColumn('permission_user', 'team_id'));

        $migration->down();

        $this->assertFalse(Schema::hasTable('roles'));
        $this->assertFalse(Schema::hasTable('teams'));
    }

    public function test_the_teams_migration_runs_and_rolls_back_after_the_migration(): void
    {
        File::ensureDirectoryExists($this->directory.'/database/migrations');
        $this->artisan('laratrust:migration')
            ->expectsConfirmation('Proceed with the migration creation?', 'yes')
            ->assertSuccessful();
        $this->app['config']->set('laratrust.teams.enabled', true);
        $this->artisan('laratrust:setup-teams')
            ->expectsConfirmation('Proceed with the migration creation?', 'yes')
            ->assertSuccessful();

        $tables = $this->requireMigration('laratrust_setup_tables');
        $teams = $this->requireMigration('laratrust_setup_teams');
        $tables->up();
        $teams->up();

        $this->assertTrue(Schema::hasTable('teams'));
        $this->assertTrue(Schema::hasColumn('role_user', 'team_id'));
        $this->assertTrue(Schema::hasColumn('permission_user', 'team_id'));

        $teams->down();
        $this->assertFalse(Schema::hasTable('teams'));
        $tables->down();
        $this->assertFalse(Schema::hasTable('roles'));
    }

    public function test_creating_the_migration_again_warns_about_the_existing_one(): void
    {
        File::ensureDirectoryExists($this->directory.'/database/migrations');
        File::put($this->directory.'/database/migrations/2020_01_01_000000_laratrust_setup_tables.php', '<?php');

        $this->artisan('laratrust:migration')
            // One write: "Laratrust migration already exists. Following file was found: - {file}".
            ->expectsOutputToContain('2020_01_01_000000_laratrust_setup_tables.php')
            ->expectsConfirmation('Proceed with the migration creation?', 'no')
            ->assertSuccessful();
    }

    public function test_the_teams_migration_warns_about_an_existing_one(): void
    {
        $this->app['config']->set('laratrust.teams.enabled', true);
        File::ensureDirectoryExists($this->directory.'/database/migrations');
        File::put($this->directory.'/database/migrations/2020_01_01_000000_laratrust_setup_teams.php', '<?php');

        $this->artisan('laratrust:setup-teams')
            ->expectsOutputToContain('2020_01_01_000000_laratrust_setup_teams.php')
            ->expectsConfirmation('Proceed with the migration creation?', 'no')
            ->assertSuccessful();
    }

    /**
     * Load the generated migration with the given suffix, which must return its instance.
     */
    protected function requireMigration(string $suffix): Migration
    {
        $files = File::glob($this->directory."/database/migrations/*_{$suffix}.php");
        $this->assertCount(1, $files);

        $migration = require $files[0];
        $this->assertInstanceOf(Migration::class, $migration);

        return $migration;
    }

    protected function assertValidPhp(string $code): void
    {
        // Throws a ParseError on invalid code.
        token_get_all($code, TOKEN_PARSE);

        $this->addToAssertionCount(1);
    }
}
