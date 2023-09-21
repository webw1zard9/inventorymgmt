<?php

namespace App\Listeners;

use App\Events\BatchCreated;
use App\PurchaseOrder;
use Illuminate\Support\Facades\Auth;

class BatchCreatedListener
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
    public function handle(BatchCreated $event)
    {
        $batchObj = $event->batch;
        $po = PurchaseOrder::find($batchObj->purchase_order_id);

        $activity_prop = collect([
            'PO' => ($po->ref_number??"--"),
            'Vendor' => ($po ? $po->vendor->name : "--"),
            'Category' => $batchObj->category->name,
            'Name' => $batchObj->name,
            'SKU' => $batchObj->ref_number,
            'Qty' => $batchObj->units_purchased.' '.$batchObj->uom,
            'Cost' => display_currency($batchObj->unit_price),
            'Sugg. Sale Price' => display_currency($batchObj->suggested_unit_sale_price),
            'Flex' => display_currency($batchObj->min_flex),
        ]);

        activity('batch')
            ->causedBy(Auth::user())
            ->performedOn($batchObj)
            ->withProperties($activity_prop)
            ->log('Created');

        if($po) {

            $activity_prop = collect([
                'Id' => $batchObj->id,
                'Category' => $batchObj->category->name,
                'Name' => $batchObj->name,
                'Short Name' => $batchObj->description,
                'SKU' => $batchObj->ref_number,
                'Qty' => $batchObj->units_purchased.' '.$batchObj->uom,
                'Cost' => display_currency($batchObj->unit_price),
                'Sugg. Sale Price' => display_currency($batchObj->suggested_unit_sale_price),
                'Flex' => display_currency($batchObj->min_flex),
            ]);

            activity('purchase_order')
                ->causedBy(Auth::user())
                ->performedOn($po)
                ->withProperties($activity_prop)
                ->log('Item Added');
        }

    }
}
