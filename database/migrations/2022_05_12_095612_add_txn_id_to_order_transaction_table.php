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
            $table->char('journal_transaction_id', 36)->after('txn_date')->index()->nullable();
            $table->foreign('journal_transaction_id')->references('id')->on('accounting_journal_transactions')->onDelete('cascade');
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
            $table->dropForeign(['journal_transaction_id']);
            $table->dropIndex(['journal_transaction_id']);
            $table->dropColumn('journal_transaction_id');
        });
    }
};
