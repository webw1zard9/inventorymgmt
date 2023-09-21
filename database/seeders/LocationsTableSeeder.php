<?php

namespace Database\Seeders;

use App\ChartOfAccount;
use App\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Scottlaurent\Accounting\Models\Ledger;

class LocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        try {

            Location::create(['name' => 'Nest', 'active' => 1]);

        } catch (\Exception $e) {
            $this->command->error($e->getMessage());
            return false;
        }
    }
}
