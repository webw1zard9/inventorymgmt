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
            $table->integer('return_purchase_order_id')->nullable()->unsigned()->index()->after('sale_order_id');
            $table->foreign('return_purchase_order_id')->references('id')->on('orders');
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
            $table->dropForeign(['return_purchase_order_id']);
            $table->dropIndex(['return_purchase_order_id']);
            $table->dropColumn('return_purchase_order_id');
        });
    }
};
