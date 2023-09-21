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
        Schema::create('order_details', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('sale_order_id')->unsigned()->index();
            $table->foreign('sale_order_id')->references('id')->on('orders');

            $table->integer('return_order_id')->nullable()->unsigned()->index();
            $table->foreign('return_order_id')->references('id')->on('orders');

            $table->integer('batch_id')->unsigned()->index();
            $table->foreign('batch_id')->references('id')->on('batches');

            $table->string('sold_as_name')->nullable();

            $table->float('units')->unsigned();

            $table->integer('unit_cost')->nullable();
            $table->integer('unit_sale_price')->nullable();
            $table->integer('subtotal_sale_price')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_details');
    }
};
