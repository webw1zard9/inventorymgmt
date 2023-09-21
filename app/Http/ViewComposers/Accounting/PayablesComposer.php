<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\Accounting;

use App\Location;
use App\Order;
use Illuminate\View\View;

class PayablesComposer
{
    public function compose(View $view)
    {
        $location_aggregate_owed = [];

        foreach ($view->vendors as $vendor) {
            foreach ($vendor->purchase_orders as &$purchase_order) {
                $purchase_order->loadLocationBalances();

//                dd($purchase_order->location_cost_owed);

                foreach ($purchase_order->location_cost_owed as $location_name => $location_data) {
                    @$location_aggregate_owed[$location_name] += $location_data['total_owed'];
                }
            }
        }

        //remove zeros
        foreach($location_aggregate_owed as $key=>$value) {
            if(!$value) {
                unset($location_aggregate_owed[$key]);
            }
        }

//dd('d');
//        dd($view->vendors);
//        dd($location_aggregate_owed);
        $view->with('location_aggregate_owed', $location_aggregate_owed);
    }
}
