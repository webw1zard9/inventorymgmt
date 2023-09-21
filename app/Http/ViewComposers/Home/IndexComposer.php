<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\Home;

use Illuminate\Support\Collection;
use Illuminate\View\View;

class IndexComposer
{
    public function compose(View $view)
    {

//        $view->category_location_inventory->groupBy('location')->each(function($location_inventory, $location) use ($view) {
//
//                $location_inventory_arr[] = ['Category','Inventory Value'];
//                $location_inventory->sortBy('category')->groupBy('category')->each(function($inventory, $category) use (&$location_inventory_arr) {
//
//                $location_inventory_arr[] = [$category, $inventory->sum('inventory_value')];
//
//            });
//
//            $view->location_inventory_arr = $location_inventory_arr;
//
//        });

        //group all customers collection
//        $customers_by_days = [];
//        if($view->customers) {
//
//            $view->customers->map(function ($item) use (&$customers_by_days) {
//
//                switch(true) {
//                    case $item->days_last_order >= 60:
//                        $customers_by_days['60']['label'] = "More than 60 days";
//                        $customers_by_days['60']['customers'][] = $item;
//                        break;
//                    case $item->days_last_order >= 30:
//                        $customers_by_days['30']['label'] = "30 - 60 Days";
//                        $customers_by_days['30']['customers'][] = $item;
//                        break;
//                    case $item->days_last_order >= 15:
//                        $customers_by_days['15']['label'] = "15 - 30 days";
//                        $customers_by_days['15']['customers'][] = $item;
//                        break;
//                    default:
//                        $customers_by_days['0']['label'] = "Less than 15";
//                        $customers_by_days['0']['customers'][] = $item;
//                        break;
//                }
//            });
//
//            $view->with(compact('customers_by_days'));
//        }
    }
}
