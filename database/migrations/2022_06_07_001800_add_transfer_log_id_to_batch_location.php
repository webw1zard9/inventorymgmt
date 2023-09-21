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
            $table->integer('transfer_log_id')->nullable()->unsigned()->index()->after('order_detail_id');
            $table->foreign('transfer_log_id')->references('id')->on('transfer_logs');
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
            $table->dropForeign(['transfer_log_id']);
            $table->dropIndex(['transfer_log_id']);
            $table->dropColumn('transfer_log_id');
        });
    }
};
