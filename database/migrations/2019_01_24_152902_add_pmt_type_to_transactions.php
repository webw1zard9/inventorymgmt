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
        Schema::table('order_transactions', function (Blueprint $table) {
            $table->string('payment_method')->after('type');
            $table->string('ref_number')->after('payment_method')->nullable();
            $table->mediumText('memo')->after('ref_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_transactions', function (Blueprint $table) {
            $table->dropColumn('payment_method');
            $table->dropColumn('ref_number');
            $table->dropColumn('memo');
        });
    }
};
