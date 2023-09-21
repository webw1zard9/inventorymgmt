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
        Schema::table('transfer_logs', function (Blueprint $table) {
            $table->mediumText('notes')->nullable()->after('reason');
            \DB::statement("ALTER TABLE `transfer_logs` CHANGE `reason` `reason` VARCHAR(255)  CHARACTER SET utf8mb4  COLLATE utf8mb4_unicode_ci  NULL  DEFAULT ''");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transfer_logs', function (Blueprint $table) {
            $table->dropColumn('notes');
            \DB::statement('ALTER TABLE `transfer_logs` CHANGE `reason` `reason` MEDIUMTEXT  CHARACTER SET utf8mb4  COLLATE utf8mb4_unicode_ci  NULL');
        });
    }
};
