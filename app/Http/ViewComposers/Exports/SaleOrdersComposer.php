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
//        return;
        $total_units_sold = [];
        $total_grams_sold = [];
        $total_lbs_sold = 0;
        $units_purchased = [];

        $sale_orders = $view->sale_orders;

        if (is_null($sale_orders)) {
            return;
        }

//        $sale_orders->load(['order_details','order_details.batch']);

        $cost_collection = new Collection();
        foreach ($sale_orders as $sale_order) {
            if ($cost_collection->isEmpty()) {
                $cost_collection = $sale_order->cost_by_fund;
            } else {
                $sale_order->cost_by_fund->map(function ($item, $key) use ($cost_collection) {
                    if ($cost_collection->has($key)) {
                        $cost_collection[$key] = $cost_collection[$key]->concat($item);
                    } else {
                        $cost_collection->put($key, $item);
                    }
                });
            }

            foreach ($sale_order->order_details_cog as $order_detail) {
                if (empty($order_detail->batch)) {
                    continue;
                }

                $uom = $order_detail->batch->uom;
                $grams = get_grams($uom);

                @$units_purchased[$sale_order->id][$uom] += (! is_null($order_detail->units_accepted) ? $order_detail->units_accepted : $order_detail->units);
                @$total_units_sold[$uom] += (! is_null($order_detail->units_accepted) ? $order_detail->units_accepted : $order_detail->units);
                @$total_grams_sold[$uom] += (! is_null($order_detail->units_accepted) ? $order_detail->units_accepted : $order_detail->units) * $grams;
            }
            $sale_order->units_purchased = (! empty($units_purchased[$sale_order->id]) ? $units_purchased[$sale_order->id] : 0);
        }

        $total_lbs_sold = array_sum($total_grams_sold) / config('inventorymgmt.conversions.grams_per_pound');

        $totals = [];
        foreach ($units_purchased as $sid => &$units) {
            foreach ($units as $uom => $v) {
                $units[$uom] = $v.' '.$uom;

                try {
                    $grams = get_grams($uom);

                    if ($v) {
                        if (empty($totals[$sid])) {
                            $totals[$sid]['grams'] = 0;
                        }
                        $totals[$sid]['grams'] += $v * $grams;
                    }
                } catch (\Exception $e) {
                    dd($e->getMessage());
                }
            }

            if (! empty($totals[$sid])) {
                $totals[$sid]['lbs'] = round($totals[$sid]['grams'] / config('inventorymgmt.conversions.grams_per_pound'), 2);
            }
        }

//        dump($units_purchased);
//        dump($total_units_sold);
//        dump($total_grams_sold);

//        $view->sale_orders_grouped_by_status = $view->sale_orders->groupBy('status');

        $view->with('units_purchased', $units_purchased)
            ->with('totals', $totals)
            ->with('total_units_sold', $total_units_sold)
            ->with('total_grams_sold', $total_grams_sold)
            ->with('total_lbs_sold', $total_lbs_sold)
            ->with('cost_collection', $cost_collection);
    }
}
