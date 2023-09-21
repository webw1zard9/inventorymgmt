<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\SaleOrders;

use Illuminate\View\View;

class DiscountApprovalComposer
{
    public function compose(View $view)
    {
        foreach ($view->all_order_discounts_approval as &$sale_order) {
            $lines_need_approval = 0;
            $lines_discount_total = 0;

            foreach ($sale_order->order_details as &$order_detail) {
//                dump($order_detail);
                if ($order_detail->batch_location && ! $order_detail->batch_location->price_approved) {
                    $lines_need_approval++;
                    $lines_discount_total += $order_detail->lineDiscount;
                }
            }

            $sale_order->no_lines_require_discount_approval = $lines_need_approval;
            $sale_order->items_require_discount_approval = $lines_discount_total;
        }
    }
}
