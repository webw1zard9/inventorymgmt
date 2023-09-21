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
            $table->dropForeign(['sale_order_id']);
            $table->dropIndex(['sale_order_id']);
            $table->dropColumn('sale_order_id');

            $table->integer('order_detail_id')->nullable()->unsigned()->index()->after('location_id');
            $table->foreign('order_detail_id')->references('id')->on('order_details')->onDelete('cascade');
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
            $table->integer('sale_order_id')->nullable()->unsigned()->index()->after('location_id');
            $table->foreign('sale_order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->dropForeign(['order_detail_id']);
            $table->dropIndex(['order_detail_id']);
            $table->dropColumn('order_detail_id');
        });
    }
};
