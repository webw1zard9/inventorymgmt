<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReceivablesAgingComposer
{
    public function compose(View $view)
    {
        //60+
        //30+
        //15+

        $view->sale_orders->map(function ($item) {
            $due_date = ($item->due_date ? $item->due_date : $item->txn_date);

            if ($due_date->lt(Carbon::parse('-3 month'))) {
                $item['age'] = '90 + days';
            } elseif ($due_date->lt(Carbon::parse('-2 month'))) {
                $item['age'] = '60 + days';
            } elseif ($due_date->lt(Carbon::parse('-1 month'))) {
                $item['age'] = '30 + days';
            } elseif ($due_date->lt(Carbon::parse('-15 day'))) {
                $item['age'] = '15 + days';
            } else {
                $item['age'] = 'Less than 15 days';
            }
//
//
//            $item->date_ago = $item->txn_date->diffForHumans();
//            return $item;
        });

//        dd($view->sale_orders->groupBy('age'));
//
//        dump($view->orders);
//        dd($view->orders->groupBy('human_date'));
//
//        foreach($view->orders as $sale_order) {
//            dump($sale_order);
//        }
//
//        $outstanding_orders = new Collection();
//
//        $view->with('outstanding_orders', $outstanding_orders);
    }
}
