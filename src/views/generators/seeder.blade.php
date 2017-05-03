<?php echo '<?php' ?>


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

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
        $hasLevels = Schema::hasColumn(config('laratrust.roles_table'), 'level');
        $config = config('laratrust_seeder.role_structure');
        if($hasLevels){
            $roleLevels = config('laratrust_seeder.levels_map');
        }
        $userPermission = config('laratrust_seeder.permission_structure');
        $mapPermission = collect(config('laratrust_seeder.permissions_map'));

        foreach ($config as $key => $modules) {
            // Create a new role
            $role = \{{ $role }}::create([
                'name' => $key,
                'display_name' => ucwords(str_replace("_", " ", $key)),
                'description' => ucwords(str_replace("_", " ", $key))
            ]);
            if($hasLevels){
                $role->level = $roleLevels[$key];
            }
            $this->command->info('Creating Role '. strtoupper($key));

            // Reading role permission modules
            foreach ($modules as $module => $value) {
                $permissions = explode(',', $value);

                foreach ($permissions as $p => $perm) {
                    $permissionValue = $mapPermission->get($perm);

                    $permission = \{{ $permission }}::firstOrCreate([
                        'name' => $permissionValue . '-' . $module,
                        'display_name' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                        'description' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                    ]);

                    $this->command->info('Creating Permission to '.$permissionValue.' for '. $module);
                    
                    if (!$role->hasPermission($permission->name)) {
                        $role->attachPermission($permission);
                    } else {
                        $this->command->info($key . ': ' . $p . ' ' . $permissionValue . ' already exist');
                    }
                }
            }

            $this->command->info("Creating '{$key}' user");
            // Create default user for each role
            $user = \{{ $user }}::create([
                'name' => ucwords(str_replace("_", " ", $key)),
                'email' => $key.'@app.com',
                'password' => bcrypt('password'),
                'remember_token' => str_random(10),
            ]);
            $user->attachRole($role);
        }

        // creating user with permissions
        if (!empty($userPermission)) {
            foreach ($userPermission as $key => $modules) {
                foreach ($modules as $module => $value) {
                    $permissions = explode(',', $value);
                    // Create default user for each permission set
                    $user = \{{ $user }}::create([
                        'name' => ucwords(str_replace("_", " ", $key)),
                        'email' => $key.'@app.com',
                        'password' => bcrypt('password'),
                        'remember_token' => str_random(10),
                    ]);
                    foreach ($permissions as $p => $perm) {
                        $permissionValue = $mapPermission->get($perm);

                        $permission = \{{ $permission }}::firstOrCreate([
                            'name' => $permissionValue . '-' . $module,
                            'display_name' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                            'description' => ucfirst($permissionValue) . ' ' . ucfirst($module),
                        ]);

                        $this->command->info('Creating Permission to '.$permissionValue.' for '. $module);
                        
                        if (!$user->hasPermission($permission->name)) {
                            $user->attachPermission($permission);
                        } else {
                            $this->command->info($key . ': ' . $p . ' ' . $permissionValue . ' already exist');
                        }
                    }
                }
            }
        }
    }

    /**
     * Truncates all the laratrust tables and the users table
     * @return  void
     */
    public function truncateLaratrustTables()
    {
@if (Config::get('database.default') == 'pgsql')
        DB::table('{{ config('laratrust.permission_role_table') }}')->truncate();
        DB::table('{{ config('laratrust.role_user_table') }}')->truncate();
        $usersTable = (new \{{ $user }})->getTable();
        $rolesTable = (new \{{ $role }})->getTable();
        $permissionsTable = (new \{{ $permission }})->getTable();
        DB::statement("TRUNCATE TABLE {$usersTable} CASCADE");
        DB::statement("TRUNCATE TABLE {$rolesTable} CASCADE");
        DB::statement("TRUNCATE TABLE {$permissionsTable} CASCADE");
@elseif(Config::get('database.default') == 'mysql')
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('{{ config('laratrust.permission_role_table') }}')->truncate();
        DB::table('{{ config('laratrust.role_user_table') }}')->truncate();
        \{{ $user }}::truncate();
        \{{ $role }}::truncate();
        \{{ $permission }}::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
@elseif(Config::get('database.default') == 'sqlsrv')
        DB::statement('EXEC sp_msforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all"');
        DB::table('{{ config('laratrust.permission_role_table') }}')->truncate();
        DB::table('{{ config('laratrust.role_user_table') }}')->truncate();
        \{{ $user }}::truncate();
        \{{ $role }}::truncate();
        \{{ $permission }}::truncate();
        DB::statement('EXEC sp_msforeachtable "ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all"');
@endif
    }
}
