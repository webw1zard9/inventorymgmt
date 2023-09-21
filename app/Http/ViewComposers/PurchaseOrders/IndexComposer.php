<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\PurchaseOrders;

use Illuminate\View\View;

class IndexComposer
{
    public function compose(View $view)
    {
        $units_purchased = [];
        $units_purchased_total = [];

        $units = [];

        $purchase_orders = &$view->purchase_orders;
        foreach ($purchase_orders->groupBy('customer_type') as $customer_type => &$pos) {
            foreach ($pos as &$po) {
                foreach ($po->batches->groupBy('status') as $status => $items) {
                    foreach ($items->groupBy('uom') as $uom => $items2) {

                        @$units[$po->id][$uom] += $items2->sum('units_purchased').' '.$uom;
                        @$units_purchased[$customer_type][$status][$uom] += $items2->sum('units_purchased');
                        @$units_purchased_total[$customer_type][$status]['total_grams'] += ($items2->sum('units_purchased'));
                    }
                }
            }
        }

        $unit_display = collect();
        foreach ($units as $po_id => $uoms) {
            $ar = [];
            foreach ($uoms as $uom => $cnt) {
                $ar[] = $cnt.' '.$uom;
            }
            $unit_display->put($po_id, $ar);
        }

        $view->with('units_purchased', $units_purchased)->with('units_purchased_total', $units_purchased_total)->with('unit_display', $unit_display);
    }
}
