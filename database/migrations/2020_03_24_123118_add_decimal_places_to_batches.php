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
        Schema::table('batches', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `batches` CHANGE `units_purchased` `units_purchased` DOUBLE(10,4)  UNSIGNED  NOT NULL  DEFAULT '0.0000'");
            \DB::statement("ALTER TABLE `batches` CHANGE `inventory` `inventory` DOUBLE(10,4)  UNSIGNED  NOT NULL  DEFAULT '0.0000'");
            \DB::statement("ALTER TABLE `batches` CHANGE `transit` `transit` DOUBLE(10,4)  UNSIGNED  NOT NULL  DEFAULT '0.0000'");
            \DB::statement("ALTER TABLE `batches` CHANGE `transfer` `transfer` DOUBLE(10,4)  UNSIGNED  NOT NULL  DEFAULT '0.0000'");
            \DB::statement("ALTER TABLE `batches` CHANGE `sold` `sold` DOUBLE(10,4)  UNSIGNED  NOT NULL  DEFAULT '0.0000'");

            \DB::statement('ALTER TABLE `order_details` CHANGE `units` `units` DOUBLE(10,4)  NULL  DEFAULT NULL');
            \DB::statement('ALTER TABLE `order_details` CHANGE `units_accepted` `units_accepted` DOUBLE(10,4)  NULL  DEFAULT NULL');

            \DB::statement("ALTER TABLE `transfer_log_details` CHANGE `units` `units` DOUBLE(10,4)  UNSIGNED  NOT NULL  DEFAULT '0.0000'");

            \DB::statement('ALTER TABLE `transfer_logs` CHANGE `quantity_transferred` `quantity_transferred` DECIMAL(10,4)  NOT NULL');
            \DB::statement("ALTER TABLE `transfer_logs` CHANGE `inventory_loss_grams` `inventory_loss_grams` DOUBLE(10,4)  NOT NULL  DEFAULT '0.0000'");
            \DB::statement("ALTER TABLE `transfer_logs` CHANGE `shortage_grams` `shortage_grams` DOUBLE(10,4)  NOT NULL  DEFAULT '0.0000'");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('batches', function (Blueprint $table) {
            \DB::statement("ALTER TABLE `batches` CHANGE `units_purchased` `units_purchased` DOUBLE(8,2)  UNSIGNED  NOT NULL  DEFAULT '0.00'");
            \DB::statement("ALTER TABLE `batches` CHANGE `inventory` `inventory` DOUBLE(8,2)  UNSIGNED  NOT NULL  DEFAULT '0.00'");
            \DB::statement("ALTER TABLE `batches` CHANGE `transit` `transit` DOUBLE(8,2)  UNSIGNED  NOT NULL  DEFAULT '0.00'");
            \DB::statement("ALTER TABLE `batches` CHANGE `transfer` `transfer` DOUBLE(8,2)  UNSIGNED  NOT NULL  DEFAULT '0.00'");
            \DB::statement("ALTER TABLE `batches` CHANGE `sold` `sold` DOUBLE(8,2)  UNSIGNED  NOT NULL  DEFAULT '0.00'");

            \DB::statement('ALTER TABLE `order_details` CHANGE `units` `units` DOUBLE(8,2)  NULL  DEFAULT NULL');
            \DB::statement('ALTER TABLE `order_details` CHANGE `units_accepted` `units_accepted` DOUBLE(8,2)  NULL  DEFAULT NULL');

            \DB::statement("ALTER TABLE `transfer_log_details` CHANGE `units` `units` DOUBLE(8,2)  UNSIGNED  NOT NULL  DEFAULT '0.0000'");

            \DB::statement('ALTER TABLE `transfer_logs` CHANGE `quantity_transferred` `quantity_transferred` DECIMAL(8,2)  NOT NULL');
            \DB::statement("ALTER TABLE `transfer_logs` CHANGE `inventory_loss_grams` `inventory_loss_grams` DOUBLE(8,2)  NOT NULL  DEFAULT '0.0000'");
            \DB::statement("ALTER TABLE `transfer_logs` CHANGE `shortage_grams` `shortage_grams` DOUBLE(8,2)  NOT NULL  DEFAULT '0.0000'");
        });
    }
};
