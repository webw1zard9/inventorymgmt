<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\Users\Vendors;

use App\ChartOfAccount;
use App\Order;
use App\OrderTransaction;
use App\PurchaseOrder;
use Illuminate\View\View;

class VendorStatementComposer
{
    public function compose(View $view)
    {

        $running_balance = 0;

        //starting balance
        foreach($view->previous_POs as $previous_PO) {
            $running_balance = (float)bcadd($running_balance, $previous_PO->total, 2);
        }

        foreach($view->previous_vendor_transactions as $previous_vendor_transaction) {
            $running_balance = (float)bcadd($running_balance, $previous_vendor_transaction->amount * -1, 2);
        }

        $view->with('starting_balance', $running_balance);

        foreach($view->all_po_and_txns as &$all_po_and_txn) {

            if($all_po_and_txn instanceof PurchaseOrder) {
                $running_balance = (float)bcadd($running_balance, $all_po_and_txn->total, 2);
            } else {
                $running_balance = (float)bcadd($running_balance, $all_po_and_txn->amount * -1, 2);
            }

            $all_po_and_txn->running_balance = $running_balance;
        }

        $view->with('final_balance', $running_balance);
    }
}
