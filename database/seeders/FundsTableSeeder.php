<?php

namespace Database\Seeders;

use App\Fund;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class FundsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Fund::create(['name' => 'Primary']);
    }
}
