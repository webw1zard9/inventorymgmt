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
            $table->mediumText('reason')->after('type')->nullable();
            \DB::statement('ALTER TABLE `transfer_logs` CHANGE `quantity_transferred` `quantity_transferred` DECIMAL(8,2)  NOT NULL');
            \DB::statement("ALTER TABLE `transfer_logs` CHANGE `inventory_loss_grams` `inventory_loss_grams` DOUBLE(8,2)  NOT NULL  DEFAULT '0.00'");
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
            $table->dropColumn('reason');
            \DB::statement('ALTER TABLE `transfer_logs` CHANGE `quantity_transferred` `quantity_transferred` DECIMAL(8,2) UNSIGNED NOT NULL');
            \DB::statement("ALTER TABLE `transfer_logs` CHANGE `inventory_loss_grams` `inventory_loss_grams` DOUBLE(8,2) UNSIGNED NOT NULL  DEFAULT '0.00'");
        });
    }
};
