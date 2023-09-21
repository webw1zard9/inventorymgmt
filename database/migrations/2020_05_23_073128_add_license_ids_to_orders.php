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
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('origin_license_id')->nullable()->unsigned()->index()->after('fund_id');
            $table->foreign('origin_license_id')->references('id')->on('licenses');

            $table->integer('destination_license_id')->nullable()->unsigned()->index()->after('origin_license_id');
            $table->foreign('destination_license_id')->references('id')->on('licenses');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['origin_license_id']);
            $table->dropIndex(['origin_license_id']);
            $table->dropColumn('origin_license_id');

            $table->dropForeign(['destination_license_id']);
            $table->dropIndex(['destination_license_id']);
            $table->dropColumn('destination_license_id');
        });
    }
};
