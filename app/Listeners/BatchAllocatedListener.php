<?php

namespace App\Listeners;

use App\Location;
use App\PurchaseOrder;
use Illuminate\Support\Facades\Auth;

class BatchAllocatedListener
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
        $batch_allocation_data = $event->batch_allocation_data;

        $purchase_order = PurchaseOrder::find($batch->purchase_order_id);

        $destination_location = Location::find($batch->destination_location_id);
        if(is_null($destination_location)) {
            throw new \Exception("Error: Location not active. Cannot add item.");
        }

        $activity_prop = collect([
            'Origin Location' => ($batch->origin_location_id ? Location::find($batch->origin_location_id)->name : 'Nest'),
            'Destination Location' => $destination_location->name,
            'Name' => $batch_allocation_data['name'],
            'Qty' => $batch_allocation_data['quantity'].' '.$batch->uom,
            'Unit Price' => display_currency($batch_allocation_data['unit_price']),
            'Suggested Unit Sale Price' => display_currency($batch_allocation_data['suggested_unit_sale_price']),
            'Min Flex' => display_currency($batch_allocation_data['min_flex']),
        ]);

        collect($batch_allocation_data)->each(function ($item, $key) use ($activity_prop, $batch) {
            switch ($key) {
                case 'quantity':
                    $new_key = 'Qty';
                    $new_value = $item.' '.$batch->uom;
                    break;
                case 'unit_price':
                case 'suggested_unit_sale_price':
                case 'min_flex':
                    $new_key = clean_field_label($key);
                    $new_value = display_currency($item);
                    break;
                case 'approved_at':
                    $new_key = clean_field_label($key);
                    $new_value = $item->format(config('inventorymgmt.date_time_format'));
                    break;
                default:
                    $new_key = clean_field_label($key);
                    $new_value = $item;
                    break;
            }

            $activity_prop->put($new_key, $new_value);
        });

        activity('batch')
            ->causedBy(Auth::user())
            ->performedOn($batch)
            ->withProperties($activity_prop)
            ->log('Allocated');

        if($purchase_order) {
            activity('purchase_order')
                ->causedBy(Auth::user())
                ->performedOn($purchase_order)
                ->withProperties($activity_prop)
                ->log('Item Allocated');
        }
    }
}
