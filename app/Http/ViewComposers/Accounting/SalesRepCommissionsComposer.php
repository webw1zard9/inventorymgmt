<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/5/18
 * Time: 16:52
 */

namespace App\Http\ViewComposers\Accounting;

use Illuminate\View\View;

class SalesRepCommissionsComposer
{
    public function compose(View $view)
    {
        if (is_null($view->sale_orders)) {
            return;
        }

        $sale_orders = $view->sale_orders;

        $view->sale_orders = $sale_orders->transform(function ($sale_order, $key) use ($view) {
            $sale_order->bulk_order = collect($sale_order->units_purchased)->has('lb');

            $sale_order->days_since_first_order = $view->end_date->diffInDays($sale_order->customer->first_sale_order->txn_date);

            $sale_order->new_account = ($sale_order->days_since_first_order <= 60 ? 1 : 0);

            $sale_order->comm_rate = (($view->sales_rep->hasRole('salesmanager') ||
                $sale_order->bulk_order ||
                ! empty($sale_order->customer->details['house_account'])) ? 0.01 : ($sale_order->new_account || $sale_order->customer->sale_orders->count() == 1 ? 0.07 : 0.07)
            );

            if (in_array($sale_order->sales_rep_id, [231, 430])) { //bettie jane & kulture
                $sale_order->comm_rate = 0.07;
            }

            $sale_order->commission = round($sale_order->subtotal_after_discount * $sale_order->comm_rate, 2);

            return $sale_order;
        })->reject(function ($sale_order) {
            return $sale_order->sales_commission_details->count() ? true : false;
        });
    }
}
