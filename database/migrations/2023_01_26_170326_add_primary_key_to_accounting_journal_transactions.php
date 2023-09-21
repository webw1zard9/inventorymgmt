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
        Schema::table('accounting_journal_transactions', function (Blueprint $table) {
            $table->increments('acct_journal_txn_pid')->unsigned()->after('transaction_group');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_journal_transactions', function (Blueprint $table) {
            $table->dropColumn('acct_journal_txn_pid');
        });
    }
};
