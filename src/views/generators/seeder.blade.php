<?php echo '<?php' ?>


use Illuminate\Database\Seeder;
use App\User;
use App\Role;
use App\Permission;

class LaratrustSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('permission_role')->truncate();
        DB::table('role_user')->truncate();
        
        User::truncate();
        Role::truncate();
        Permission::truncate();
        
        $config = config('laratrust_seeder');

        $map_permission = [
            'c' => 'create',
            'r' => 'read',
            'u' => 'update',
            'd' => 'delete'
        ];

        foreach ($config as $key => $value) {
            // Create a new role
            $role = new Role();
            $role->name         = $key;
            $role->display_name = 'User '.ucfirst($key);
            $role->description  = ucfirst($key);
            $role->save();

            $this->command->info('Creating Role '. strtoupper($key));

            // Reading role permission modules
            $modules = $value;
            foreach ($modules as $module => $permissions) {
                $_permissions = explode(',', $permissions);

                foreach ($_permissions as $p => $perm) {
                    $permission = Permission::firstOrCreate([
                        'name' => $module . '-' . $map_permission[$perm],
                        'display_name' => ucfirst($map_permission[$perm]) . ' ' . ucfirst($module),
                        'description' => ucfirst($map_permission[$perm]) . ' ' . ucfirst($module),
                    ]);

                    $this->command->info('Creating Permission to '.$map_permission[$perm].' for '. $module);

                    $exist = DB::table('permission_role')
                        ->where('role_id', $role->id)
                        ->where('permission_id', $permission->id)
                        ->first();
                    
                    if (!$exist) {
                        $role->attachPermission($permission);
                    } else {
                        $this->command->info($key . ': ' . $p . ' ' . $map_permission[$perm] . ' already exist');
                    }
                }
            }

            // Create default user for each role
            $user = new User();

            $user->name = ucfirst($key);
            $user->email = $key.'@app.com';
            $user->password = bcrypt('password');
            $user->remember_token = str_random(10);
            $user->save();
            
            $user->attachRole($role);
        }
    }
}
