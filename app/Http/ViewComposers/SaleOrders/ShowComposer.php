<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\SaleOrders;

use Illuminate\View\View;

class ShowComposer
{
    public function compose(View $view)
    {
        $actual_balance = ($view->saleOrder->balance + $view->saleOrder->discount);

        $deliver_conf_message = ($actual_balance < 0 ? 'Order is currently over paid by: '.display_currency($actual_balance * -1)."\nThis amount will be credited to the customers profile." : null);

        $view->with('deliver_conf_message', $deliver_conf_message);
    }
}
