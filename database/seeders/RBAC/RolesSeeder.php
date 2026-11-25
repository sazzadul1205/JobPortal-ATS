<?php
namespace Database\Seeders\RBAC;
use App\Models\Role;
use Illuminate\Database\Seeder;
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin' ,'level' => 100 ,'is_default' => false],
            ['name' => 'Admin',       'slug' => 'admin'       ,'level' => 90  ,'is_default' => false],
            ['name' => 'Employer',    'slug' => 'employer'    ,'level' => 50  ,'is_default' => false],
            ['name' => 'Job Seeker',  'slug' => 'job-seeker'  ,'level' => 20  ,'is_default' => true],
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
