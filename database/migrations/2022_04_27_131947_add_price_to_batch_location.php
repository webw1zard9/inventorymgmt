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
            $table->integer('suggested_unit_sale_price')->after('name')->unsigned()->nullable();
            $table->integer('min_flex')->after('suggested_unit_sale_price')->default(0);
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
            $table->dropColumn('suggested_unit_sale_price');
            $table->dropColumn('min_flex');
        });
    }
};
