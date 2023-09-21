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
        Schema::table('batches', function (Blueprint $table) {
            $table->integer('parent_id')->nullable()->unsigned()->index()->after('id');
            $table->foreign('parent_id')->references('id')->on('batches');

            $table->float('transfer')->unsigned()->default(0)->after('transit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn('parent_id');
            $table->dropColumn('transfer');
        });
    }
};
