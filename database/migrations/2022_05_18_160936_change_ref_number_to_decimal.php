<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_transactions', function (Blueprint $table) {
            \DB::statement('ALTER TABLE `order_transactions` CHANGE `ref_number` `ref_number` DOUBLE(20,10)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_transactions', function (Blueprint $table) {
            \DB::statement('ALTER TABLE `order_transactions` CHANGE `ref_number` `ref_number` VARCHAR (255)');
        });
    }
};
