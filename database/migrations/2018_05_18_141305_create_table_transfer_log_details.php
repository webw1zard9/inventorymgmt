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
        Schema::create('transfer_log_details', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('transfer_log_id')->nullable()->unsigned()->index();
            $table->foreign('transfer_log_id')->references('id')->on('transfer_logs');

            $table->integer('batch_id')->nullable()->unsigned()->index();
            $table->foreign('batch_id')->references('id')->on('batches');

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
        Schema::dropIfExists('transfer_log_details');
    }
};
