<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {

            $this->call(RolesTableSeeder::class);
            $this->call(PermissionsTableSeeder::class);
            $this->call(AccountingLedgersSeeder::class);
            $this->call(FundsTableSeeder::class);
            $this->call(CategoryTableSeeder::class);
            $this->call(LocationsTableSeeder::class);
            $this->call(UsersTableSeeder::class);

        } catch (\Exception $e) {

            $this->command->error($e->getMessage());

        }
    }
}
