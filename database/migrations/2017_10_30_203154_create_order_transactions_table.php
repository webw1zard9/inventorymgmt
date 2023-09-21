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
        Schema::create('order_transactions', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');

            $table->integer('purchase_order_id')->nullable()->unsigned()->index();
            $table->foreign('purchase_order_id')->references('id')->on('orders');

            $table->integer('sale_order_id')->nullable()->unsigned()->index();
            $table->foreign('sale_order_id')->references('id')->on('orders');

            $table->integer('return_order_id')->nullable()->unsigned()->index();
            $table->foreign('return_order_id')->references('id')->on('orders');

            $table->integer('amount');
            $table->enum('type', ['paid', 'received'])->default('paid');
            $table->date('txn_date')->nullable();

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
        Schema::dropIfExists('order_transactions');
    }
};
