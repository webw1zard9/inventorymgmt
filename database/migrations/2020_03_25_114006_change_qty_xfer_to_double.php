<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        \DB::statement("ALTER TABLE `transfer_logs` CHANGE `quantity_transferred` `quantity_transferred` DOUBLE(10,4)  NOT NULL DEFAULT '0.0000'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement('ALTER TABLE `transfer_logs` CHANGE `quantity_transferred` `quantity_transferred` DECIMAL(10,4)  NOT NULL');
    }
};
