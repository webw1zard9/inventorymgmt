<?php

namespace App\Listeners;

use App\ChartOfAccount;
use App\Events\POCreated;
use Scottlaurent\Accounting\Services\Accounting;

class POAccountingJournals
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  POCreated  $event
     * @return void
     */
    public function handle(POCreated $event)
    {
        $po = $event->purchaseOrder;

        $journal = $po->initJournal();
//<<<<<<< HEAD
//        dd($po->total);
//        $journal->creditDollars($po->total);
//        $journal->resetCurrentBalances();
//
////        if ($po->total) {
////            $transaction_group = Accounting::newDoubleEntryTransactionGroup();
////            $transaction_group->addDollarTransaction(ChartOfAccount::Inventory()->journal, 'debit', $po->total, null, $po, $po->txn_date);
////            $transaction_group->addDollarTransaction($po->vendor->journal, 'credit', $po->total, null, $po, $po->txn_date);
//=======
        $journal->credit($po->total * 100);
        $journal->resetCurrentBalances();

//        if ($po->total) {
//            $po_total_money = convert_to_cents($po->total, true);
//            $transaction_group = Accounting::newDoubleEntryTransactionGroup();
//            $transaction_group->addTransaction(ChartOfAccount::Inventory()->journal, 'debit', $po_total_money, null, $po, $po->txn_date);
//            $transaction_group->addTransaction($po->vendor->journal, 'credit', $po_total_money, null, $po, $po->txn_date);
//>>>>>>> staging
//            $transaction_group->commit();
//        }
    }
}
