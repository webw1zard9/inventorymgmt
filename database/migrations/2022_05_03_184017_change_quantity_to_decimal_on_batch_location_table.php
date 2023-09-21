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
        Schema::table('batch_location', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `batch_location` CHANGE `quantity` `quantity` DOUBLE(10,4)  NOT NULL DEFAULT '0.0000'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('batch_location', function (Blueprint $table) {
            \DB::statement('ALTER TABLE `batch_location` CHANGE `quantity` `quantity` int(11)  NOT NULL');
        });
    }
};
