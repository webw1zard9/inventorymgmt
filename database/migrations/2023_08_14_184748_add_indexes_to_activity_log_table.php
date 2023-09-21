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
        
        ALTER TABLE `activity_log` ADD FULLTEXT INDEX `description_index` (`description`);

        ALTER TABLE `activity_log` ADD INDEX `subject_id_index` (`subject_id`) USING BTREE;
        
        ALTER TABLE `activity_log` ADD INDEX `subject_type_index` (`subject_type`) USING BTREE;
        
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
