<?php

namespace Database\Seeders\RBAC;

use Illuminate\Database\Seeder;

class RBACSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding RBAC roles...');
        $this->call(RolesSeeder::class);

        $this->command->info('Seeding RBAC permissions...');
        $this->call(PermissionsSeeder::class);

        $this->command->info('Seeding RBAC role permissions...');
        $this->call(RolePermissionsSeeder::class);

        $this->command->info('Seeding RBAC module access...');
        $this->call(ModuleAccessSeeder::class);

        $this->command->info('Seeding RBAC user roles...');
        $this->call(UserRolesSeeder::class);

        $this->command->info('RBAC seeding completed!');
    }
}
