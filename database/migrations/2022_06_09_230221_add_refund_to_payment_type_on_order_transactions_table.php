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
            \DB::statement("ALTER TABLE `order_transactions` CHANGE `type` `type` ENUM('paid','received','payment','refund') not null default 'paid'");
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
            \DB::statement("ALTER TABLE `order_transactions` CHANGE `type` `type` ENUM('paid','received') not null default 'paid'");
        });
    }
};
