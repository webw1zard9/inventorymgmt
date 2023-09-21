<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        DB::unprepared("

            ALTER TABLE `locations` ADD UNIQUE INDEX `name_index` (`name`) USING BTREE;

            ALTER TABLE `orders` ADD INDEX `type_index` (`type`) USING BTREE, ADD INDEX `status_index` (`status`) USING BTREE;

        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
};
