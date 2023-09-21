<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\Users;

use App\OrderTransaction;
use Illuminate\View\View;

class ShowComposer
{
    public function compose(View $view)
    {

        //get order_transactions that are related to journal_transactions - payments that are not associated to a
        //sales order, and only to the customer journal

        $customer_transactions = OrderTransaction::whereIn('acct_journal_txn_fid', $view->user->journal->transactions->pluck('acct_journal_txn_pid'))
            ->with('location')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        //dd($customer_transactions);
        $customer_sale_order_transactions = $view->user->sale_orders->pluck('transactions');

        $customer_sale_order_transactions->each(function ($so_transactions) use (&$customer_transactions) {
            $customer_transactions = $customer_transactions->merge($so_transactions);
        });

        $sorted = $customer_transactions->sortByDesc('id');

//        $all_merged = $customer_transactions->sortBy(['created_at', 'desc']);

//        dd($all_merged->sortBy('created_at'));

        $view->with('all_customer_transactions', $sorted);
    }
}
