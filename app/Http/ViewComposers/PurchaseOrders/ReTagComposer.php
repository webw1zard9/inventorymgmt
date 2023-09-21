<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\PurchaseOrders;

use Illuminate\View\View;

class ReTagComposer
{
    public function compose(View $view)
    {
        foreach ($view->purchaseOrder->batches as &$batch) {
            $batch->margin2 += $batch->order_details->sum('margin');
            $batch->revenue2 += $batch->order_details->sum('revenue');
            $batch->grams_accepted += $batch->order_details->sum('weight_grams_accepted');
            $batch->grams_pending += $batch->order_details->sum('weight_grams_pending');
            $batch->pounds_accepted += $batch->order_details->sum('weight_pounds_accepted');
            $batch->pounds_pending += $batch->order_details->sum('weight_pounds_pending');

            if ($batch->children_batches->count()) {
                $this->loopBatches($batch->children_batches, $batch);
            }
        }
    }

    protected function loopBatches($child_batches, &$original_batch)
    {
        foreach ($child_batches as $child_batch) {
            $original_batch->margin2 += $child_batch->order_details->sum('margin');
            $original_batch->revenue2 += $child_batch->order_details->sum('revenue');
            $original_batch->grams_accepted += $child_batch->order_details->sum('weight_grams_accepted');
            $original_batch->grams_pending += $child_batch->order_details->sum('weight_grams_pending');
            $original_batch->pounds_accepted += $child_batch->order_details->sum('weight_pounds_accepted');
            $original_batch->pounds_pending += $child_batch->order_details->sum('weight_pounds_pending');

            if ($child_batch->children_batches->count()) {
                $this->loopBatches($child_batch->children_batches, $original_batch);
            }
        }
    }
}
