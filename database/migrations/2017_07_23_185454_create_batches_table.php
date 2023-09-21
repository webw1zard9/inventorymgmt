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
        Schema::create('batches', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('purchase_order_id')->nullable()->unsigned()->index();
            $table->foreign('purchase_order_id')->references('id')->on('orders');

            $table->integer('category_id')->unsigned()->index();
            $table->foreign('category_id')->references('id')->on('categories');

            $table->string('status', 30)->default('received');

            $table->string('name');
            $table->text('description')->nullable();
            $table->json('character')->nullable();
            $table->text('sales_notes')->nullable();

            $table->string('type')->nullable();
            $table->string('ref_number', 20)->unique()->index();

            $table->float('units_purchased')->unsigned()->default(0);
            $table->float('inventory')->unsigned()->default(0);
            $table->float('transit')->unsigned()->default(0);
            $table->float('sold')->unsigned()->default(0);

            $table->string('uom', 10)->default('Unit');

            $table->integer('unit_price');
            $table->integer('subtotal_price')->nullable();
            $table->integer('tax')->nullable();
            $table->integer('suggested_unit_sale_price')->unsigned()->nullable();

            $table->integer('min_flex')->default(0);
            $table->integer('max_flex')->default(0);
            $table->date('packaged_date')->nullable();

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
        Schema::dropIfExists('batches');
    }
};
