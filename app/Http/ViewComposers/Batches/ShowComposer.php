<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\Batches;

use Illuminate\Support\Str;
use Illuminate\View\View;

class ShowComposer
{
    public function compose(View $view)
    {

        $view->all_orders_by_status = $view->all_sale_orders->groupBy('status');

        $view->total_hold = 0;
        $view->total_ready_to_pack = 0;
        $view->total_ready_for_delivery = 0;
        $view->total_delivered = 0;

        $view->all_sale_orders->each(function ($sale_orders) use ($view) {

            foreach($sale_orders->order_details as $order_detail) {
                switch($sale_orders->status) {
                    case "hold":
                        $view->total_hold += $order_detail->units;
                        break;
                    case "ready to pack":
                        $view->total_ready_to_pack += bcsub($order_detail->units, $order_detail->units_fulfilled);
                        $view->total_ready_for_delivery += $order_detail->units_fulfilled;
                        break;
                    case "ready for delivery":
                        $view->total_ready_for_delivery += $order_detail->units_fulfilled;
                        break;
                    case "delivered":
                        $view->total_delivered += $order_detail->units_accepted;
                        break;
                }
            }
        });

//
//
//        dd('e');
//        $view->all_orders_by_status->each(function ($sale_orders, $status) use ($view) {
//            $view->{'total_'.Str::slug($status, '_')} = $sale_orders->sum(function ($sale_order) {
//                return $sale_order->order_details->sum('final_units');
//            });
//        });

        $view->allocated_and_sold_by_location_name = $view->batch->allocated_and_sold_inventory->groupBy('name')->sortKeys();

    }
}
