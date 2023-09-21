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
        Schema::create('batch_location_aggregate', function (Blueprint $table) {
            $table->id();

            $table->integer('batch_id')->unsigned()->index();
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');

            $table->integer('location_id')->unsigned()->index();
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');

            $table->double('onhand_inventory', 10, 4)->default(0);
            $table->integer('onhand_cost')->default(0);

            $table->double('available_inventory', 10, 4)->default(0);
            $table->integer('available_cost')->default(0);

            $table->double('pending_inventory', 10, 4)->default(0);
            $table->integer('pending_cost')->default(0);

            $table->double('fulfilled_inventory', 10, 4)->default(0);
            $table->integer('fulfilled_cost')->default(0);

            $table->double('sold_inventory', 10, 4)->default(0);
            $table->integer('sold_cost')->default(0);

            $table->double('reconciled_inventory', 10, 4)->default(0);
            $table->integer('reconciled_cost')->default(0);

            $table->double('approved_inventory', 10, 4)->default(0);
            $table->double('waiting_approval_inventory', 10, 4)->default(0);

            $table->integer('suggested_unit_sale_price')->unsigned()->nullable();
            $table->integer('min_flex')->default(0);

            $table->integer('location_unit_price')->default(0);
            $table->string('location_batch_name')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'location_id']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('batch_location_aggregate');
    }
};
