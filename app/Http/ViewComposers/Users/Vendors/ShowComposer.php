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
use Illuminate\View\View;

class ShowComposer
{
    public function compose(View $view)
    {

        //get vendor credit transactions related to this vendor only

//        $vendor_credit_txn_ids = ChartOfAccount::VendorCredits()->journal->transactionsReferencingObjectQuery($view->vendor)->pluck('acct_journal_txn_pid');

//        foreach($view->all_location_inventory as $batch) {
//            dump($batch);
//            debug($batch->inventory_value);
//        }


        $total_inventory_value = ($view->nest_inventory->sum('inventory_value') + $view->all_location_inventory->sum('inventory_value'));

        $current_payables = $view->vendor->purchase_orders->sum('balance') - $total_inventory_value - $view->pending_order_cost;

        $view->with('total_inventory_value', $total_inventory_value);
        $view->with('current_payables', $current_payables);
    }
}
