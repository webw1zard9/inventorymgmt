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
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');

            $table->integer('vendor_id')->nullable()->unsigned()->index();
            $table->foreign('vendor_id')->references('id')->on('users');

            $table->integer('customer_id')->nullable()->unsigned()->index();
            $table->foreign('customer_id')->references('id')->on('users');

            $table->date('txn_date');
            $table->date('due_date')->nullable();

            $table->enum('type', ['purchase', 'sale', 'return'])->default('purchase')->index();
            $table->string('sale_type',20)->default('')->nullable()->index();
            $table->string('status', 20)->default('open')->indexed();
            $table->string('customer_type', 30)->nullable();

            $table->string('ref_number', 20)->unique()->index()->nullable();
            $table->integer('subtotal');
            $table->integer('tax');
            $table->integer('total');
            $table->integer('balance');

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
        Schema::dropIfExists('orders');
    }
};
