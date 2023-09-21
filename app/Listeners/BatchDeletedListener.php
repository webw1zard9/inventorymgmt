<?php

namespace App\Listeners;

use App\PurchaseOrder;
use Illuminate\Support\Facades\Auth;

class BatchDeletedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $batch = $event->batch;

        $purchaseOrder = PurchaseOrder::find($batch->purchase_order_id);

        $activity_prop = collect([
            'Id' => $batch->id,
            'Category' => $batch->category->name,
            'Name' => $batch->name,
            'Short Name' => $batch->description,
            'SKU' => $batch->ref_number,
            'Qty' => $batch->units_purchased.' '.$batch->uom,
            'Cost' => display_currency($batch->unit_price),
            'Sugg. Sale Price' => display_currency($batch->suggested_unit_sale_price),
            'Flex' => display_currency($batch->min_flex),
        ]);

        activity('batch')
            ->causedBy(Auth::user())
            ->performedOn($purchaseOrder)
            ->withProperties($activity_prop)
            ->log('Item Deleted');
    }
}
