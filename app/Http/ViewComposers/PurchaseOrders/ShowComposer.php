<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\PurchaseOrders;

use Illuminate\View\View;

class ShowComposer
{
    public function compose(View $view)
    {

//        foreach($view->purchaseOrder->batches as $batch) {
//            $batch->canEditPrice = true;
//
//            if($batch->order_details->count()) {
//                $batch->canEditPrice = false;
//                continue;
//            }
//
//            if($batch->children_batches->count()) {
//                $this->loopBatches($batch->children_batches, $batch);
//            }
//
////            dd($batch);
//        }
//
//        $view->with('units_purchased', null);
    }

//    protected function loopBatches($child_batches, &$original_batch)
//    {
//        foreach($child_batches as $child_batch)
//        {
//            if($child_batch->order_details->count()) {
//                $original_batch->canEditPrice = false;
//                continue;
//            }
//
//            if($child_batch->children_batches->count()) {
//                $this->loopBatches($child_batch->children_batches, $original_batch);
//            }
//        }
//    }
}
