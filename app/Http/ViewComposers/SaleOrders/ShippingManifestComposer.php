<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\SaleOrders;

use Illuminate\View\View;

class ShippingManifestComposer
{
    public function compose(View $view)
    {
        $o_details = $view->saleOrder->order_details->sortBy('sold_as_name');

        $additional_order_details = $o_details->splice(6);
        //dump($additional_details);
        $initial_order_details = $o_details;

//        dd($initial_details);

        $view->with('additional_order_details', $additional_order_details)
            ->with('initial_order_details', $initial_order_details);
    }
}
