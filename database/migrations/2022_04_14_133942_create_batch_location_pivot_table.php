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
        Schema::create('batch_location', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('batch_id')->unsigned()->index();
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');

            $table->integer('location_id')->unsigned()->index();
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');

            $table->integer('sale_order_id')->nullable()->unsigned()->index();
            $table->foreign('sale_order_id')->references('id')->on('orders')->onDelete('cascade');

            $table->integer('quantity');
            $table->string('name');
            $table->tinyInteger('approved')->default(0);

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
        Schema::dropIfExists('batch_location');
    }
};
