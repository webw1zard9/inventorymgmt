<?php

namespace Database\Seeders;

use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0'); // disable foreign key constraints

        Role::truncate();

        Role::create([
            'description' => 'Admin',
            'name' => 'admin',
            'level' => 100,
            'guard_name' => 'web',
        ]);

        Role::create([
            'description' => 'Location Manager',
            'name' => 'locationmanager',
            'level' => 60,
            'guard_name' => 'web',
        ]);

        Role::create([
            'description' => 'Sales Rep',
            'name' => 'salesrep',
            'level' => 50,
            'guard_name' => 'web',
        ]);

        Role::create([
            'description' => 'Vendor',
            'name' => 'vendor',
            'level' => 25,
            'guard_name' => 'web',
        ]);

        Role::create([
            'description' => 'Customer',
            'name' => 'customer',
            'level' => 20,
            'guard_name' => 'web',
        ]);

        Role::create([
            'description' => 'Sauce',
            'name' => 'sauce',
            'level' => 10,
            'guard_name' => 'web',
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS = 1'); // enable foreign key constraints
    }
}
