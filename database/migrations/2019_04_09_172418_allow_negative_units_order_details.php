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
        Schema::table('order_details', function (Blueprint $table) {
            \DB::statement('ALTER TABLE `'.$table->getTable().'` CHANGE `units` `units` DOUBLE(8,2)  NULL  DEFAULT NULL');
            \DB::statement('ALTER TABLE `'.$table->getTable().'` CHANGE `units_accepted` `units_accepted` DOUBLE(8,2)  NULL  DEFAULT NULL');

            $table->integer('parent_id')->nullable()->unsigned()->index()->after('id');
            $table->foreign('parent_id')->references('id')->on('order_details');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
        });
    }
};
