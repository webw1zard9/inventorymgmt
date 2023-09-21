<?php

namespace App\Listeners;

use App\ChartOfAccount;
use App\Events\SaleOrderDelivered;
use Scottlaurent\Accounting\Services\Accounting;

class BookRevenue
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
     * @param  SaleOrderDelivered  $event
     * @return void
     */
    public function handle(SaleOrderDelivered $event)
    {
        $sale_order = $event->saleOrder;

        // this represents some kind of sale to a customer for $500 based on an invoiced ammount of 500.
        $transaction_group = Accounting::newDoubleEntryTransactionGroup();

        $so_total_money = convert_to_cents($sale_order->total, true);
        if ($sale_order->total) {
            $transaction_group->addTransaction(ChartOfAccount::PrepaidInventory()->journal, 'debit', $so_total_money, null, $sale_order->customer);  // your user journal probably is an income ledger
            $transaction_group->addTransaction(ChartOfAccount::Revenue()->journal, 'credit', $so_total_money, null, $sale_order->location); // this is an asset ledder
        }

        //debit cogs
        //credit inventory
        $so_cost_money = convert_to_cents($sale_order->cost, true);
        if ($sale_order->cost) {
            $transaction_group->addTransaction(ChartOfAccount::COG()->journal, 'debit', $so_cost_money, null, $sale_order->location);  // your user journal probably is an income ledger
            $transaction_group->addTransaction(ChartOfAccount::Inventory()->journal, 'credit', $so_cost_money, null, $sale_order->location); // this is an asset ledder
        }

        $transaction_group->commit();

        $sale_order->customer->journal->resetCurrentBalances();

//        $sale_order
        $sale_order->balance = $sale_order->total + $sale_order->balance;
        $sale_order->save();
    }
}
