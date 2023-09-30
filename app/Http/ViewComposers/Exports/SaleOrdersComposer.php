<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\Exports;

use Illuminate\Support\Collection;
use Illuminate\View\View;

class SaleOrdersComposer
{
    public function compose(View $view)
    {
        $units_purchased = [];

        $sale_orders = $view->sale_orders;

        if (is_null($sale_orders)) {
            return;
        }

        foreach ($sale_orders as $sale_order) {

            foreach ($sale_order->order_details_cog as $order_detail) {
                if (empty($order_detail->batch)) {
                    continue;
                }

                $uom = $order_detail->batch->uom;

                @$units_purchased[$sale_order->id][$uom] += (! is_null($order_detail->units_accepted) ? $order_detail->units_accepted : $order_detail->units);
            }
            $sale_order->units_purchased = (! empty($units_purchased[$sale_order->id]) ? $units_purchased[$sale_order->id] : 0);
        }

        foreach ($units_purchased as $sid => &$units) {
            foreach ($units as $uom => $v) {
                $units[$uom] = $v.' '.$uom;
            }
        }

        $view->with('units_purchased', $units_purchased);
    }
}
