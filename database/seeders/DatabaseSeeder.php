<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            GradingSystemSeeder::class,
        ]);

        // Create Super Admin user
        $superAdmin = User::factory()->create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'username' => 'superadmin',
            'email' => 'admin@sukulu.com',
            'password' => bcrypt('password'),
            'school_id' => null,
        ]);
        
        // Use sanctum guard for role assignment
        $role = \App\Models\Role::where('name', 'super-admin')->where('guard_name', 'sanctum')->first();
        if ($role) {
            $superAdmin->roles()->attach($role->id);
        }
    }
}
