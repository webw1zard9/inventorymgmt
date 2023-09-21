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

            ALTER TABLE `accounting_journals` ADD INDEX `morphed_id_index` (`morphed_id`) USING BTREE;
            
            ALTER TABLE `accounting_journals` ADD INDEX `morphed_type_index` (`morphed_type`) USING BTREE;
            
            ALTER TABLE `accounting_journal_transactions` ADD INDEX `journal_id_index` (`journal_id`) USING BTREE;
            
            ALTER TABLE `accounting_journal_transactions` ADD INDEX `ref_class_id_index` (`ref_class_id`) USING BTREE;
            
            ALTER TABLE `accounting_journal_transactions` ADD INDEX `ref_class_index` (`ref_class`) USING BTREE;
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
