<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\SaleOrders;

use Illuminate\View\View;

class InvoiceComposer
{
    public function compose(View $view)
    {
        $sale_order = $view->saleOrder;
        if (is_null($sale_order)) {
            return;
        }

        if (is_null($sale_order->bill_to)) {
            $sale_order->setRelation('bill_to', $sale_order->customer);
        }
    }
}
