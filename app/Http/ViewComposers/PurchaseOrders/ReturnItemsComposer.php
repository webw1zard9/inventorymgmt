<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\PurchaseOrders;

use Illuminate\View\View;

class ReturnItemsComposer
{
    public function compose(View $view)
    {

        $allocated_sold_inventory = collect();

        foreach($view->purchaseOrder->batches as $batch)
        {
//            dump($batch->name);
            $allocated_and_sold = $batch->allocated_and_sold_inventory->sortBy('name')->groupBy('name');

//            dump($allocated_and_sold->toArray());

            $location_approved_statuses = collect();
            foreach($allocated_and_sold as $location_name => $locations) {

                $location_approved_statuses->put($location_name, $locations->groupBy('batch_location.approved_status'));
//                dump($locations->groupBy('batch_location.approved_status'));

            }

            $allocated_sold_inventory->put($batch->id, $location_approved_statuses);

        }

//        debug($allocated_sold_inventory);

//        dd($view);


        $view->with('allocated_sold_inventory', $allocated_sold_inventory);
    }

}
