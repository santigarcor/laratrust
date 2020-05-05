<?php echo '<?php' ?>

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class LaratrustSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Truncating User, Role and Permission tables');
        $this->truncateLaratrustTables();

        $config = config('laratrust_seeder.roles_structure');
        $mapPermission = collect(config('laratrust_seeder.permissions_map'));

        foreach ($config as $key => $modules) {

            // Create a new role
            $role = \{{ $role }}::firstOrCreate([
                'name' => $key,
                'display_name' => ucwords(str_replace('_', ' ', $key)),
                'description' => ucwords(str_replace('_', ' ', $key))
            ]);
            $permissions = [];

            $this->command->info('Creating Role '. strtoupper($key));

            // Reading role permission modules
            foreach ($modules as $module => $value) {

                foreach (explode(',', $value) as $p => $perm) {

                    $permissionValue = $mapPermission->get($perm);

                    $permissions[] = \{{ $permission }}::firstOrCreate([
                        'name' => $permissionValue . '-' . $module,
                        'display_name' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                        'description' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                    ])->id;

                    $this->command->info('Creating Permission to '.$permissionValue.' for '. $module);
                }
            }

            // Attach all permissions to the role
            $role->permissions()->sync($permissions);

            if(Config::get('laratrust_seeder.create_users')) {
                $this->command->info("Creating '{$key}' user");
                // Create default user for each role
                $user = \{{ $user }}::create([
                    'name' => ucwords(str_replace('_', ' ', $key)),
                    'email' => $key.'@app.com',
                    'password' => bcrypt('password')
                ]);
                $user->attachRole($role);
            }

        }
    }

    /**
     * Truncates all the laratrust tables and the users table
     *
     * @return  void
     */
    public function truncateLaratrustTables()
    {
        Schema::disableForeignKeyConstraints();
@if (Config::get('database.default') == 'pgsql')
        DB::table('{{ config('laratrust.tables.permission_role') }}')->truncate();
        DB::table('{{ config('laratrust.tables.permission_user') }}')->truncate();
        DB::table('{{ config('laratrust.tables.role_user') }}')->truncate();
        $rolesTable = (new \{{ $role }})->getTable();
        $permissionsTable = (new \{{ $permission }})->getTable();
        if(Config::get('laratrust_seeder.truncate_tables')) {
            DB::statement("TRUNCATE TABLE {$permissionsTable} CASCADE");
            DB::statement("TRUNCATE TABLE {$rolesTable} CASCADE");
        }
        if(Config::get('laratrust_seeder.truncate_tables') && Config::get('laratrust_seeder.create_users')) {
            $usersTable = (new \{{ $user }})->getTable();
            DB::statement("TRUNCATE TABLE {$usersTable} CASCADE");
        }
@else
        DB::table('{{ config('laratrust.tables.permission_role') }}')->truncate();
        DB::table('{{ config('laratrust.tables.permission_user') }}')->truncate();
        DB::table('{{ config('laratrust.tables.role_user') }}')->truncate();
        if(Config::get('laratrust_seeder.truncate_tables')) {
            \{{ $role }}::truncate();
            \{{ $permission }}::truncate();
        }
        if(Config::get('laratrust_seeder.truncate_tables') && Config::get('laratrust_seeder.create_users')) {
            \{{ $user }}::truncate();
        }
@endif
        Schema::enableForeignKeyConstraints();
    }
}
