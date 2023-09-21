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
            $table->dropForeign(['acct_journal_txn_fid']);
            $table->dropIndex(['acct_journal_txn_fid']);

            $table->foreign('acct_journal_txn_fid')
                ->references('acct_journal_txn_pid')
                ->on('accounting_journal_transactions')
                ->onDelete('cascade');
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
            $table->dropForeign(['acct_journal_txn_fid']);
            $table->dropIndex(['acct_journal_txn_fid']);
            $table->foreign('acct_journal_txn_fid')
                ->references('acct_journal_txn_pid')
                ->on('accounting_journal_transactions');
        });
    }
};
