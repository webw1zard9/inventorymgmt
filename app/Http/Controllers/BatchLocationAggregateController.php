<?php

namespace App\Http\Controllers;

use App\BatchLocation;
use App\BatchLocationAggregate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BatchLocationAggregateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\BatchLocationAggregate  $batchLocationAggregate
     * @return \Illuminate\Http\Response
     */
    public function show(BatchLocationAggregate $batchLocationAggregate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\BatchLocationAggregate  $batchLocationAggregate
     * @return \Illuminate\Http\Response
     */
    public function edit(BatchLocationAggregate $batchLocationAggregate)
    {
        view()->share('title', 'Edit Allocation');

        $batchLocationAggregate->load('batch', 'location', 'batch.category');
//        dd($batchLocation);


//        $batchBuilder = $batchLocation->batch();
//        if (Auth::user()->hasLocation()) {
//            $batchBuilder->currentLocationUnitPrice();
//        }
//        $batch = $batchBuilder->first();

        $batch = $batchLocationAggregate->batch;
        $location = $batchLocationAggregate->location;

//        dd($location->batches_aggregate()->where('batch_id', $batch->id)->get());



        $remaining_inventory = $location->remainingInventory($batch->id);
        $remaining_po_inventory = $location->remainingPoInventory($batch->id);

//        dump($location);
//dd($batch);
        return view('batch_location_aggregate.edit', compact(
            'batchLocationAggregate',
            'batch',
            'location'
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\BatchLocationAggregate  $batchLocationAggregate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BatchLocationAggregate $batchLocationAggregate)
    {
        //dd($request->all());

        if ($request->has('_edit_batch_location_aggregate')) {

            try {
                DB::beginTransaction();
//                dump($request->all());

                $current_price = $batchLocationAggregate->location_unit_price;

//                $current_price = $batchLocation->batch->cost_by_location[$batchLocation->location_id];
//                dump($current_price);
//                dd($batchLocationAggregate);
                $price_change=0;

                if($request->has('new_unit_cost')) {
                    $qty_to_change_cost = $request->get('qty_to_change_unit_cost');

                    if($qty_to_change_cost < 0 || bccomp($qty_to_change_cost, $batchLocationAggregate->batch->units_purchased) === 1) { //change qty is > than purchased
                        throw new \Exception("Invalid quantity. Range: 0-".$batchLocationAggregate->batch->units_purchased);
                    }

                    $new_price = $request->get('new_unit_cost');
                    $price_change = (float)bcsub($new_price, $current_price, 2);
                }
//dd($price_change);
                if ($price_change != 0) { //cost change
                    //vendor credit
                    $batch = $batchLocationAggregate->batch;

//                    $remaining_qty = $batchLocation->location->remainingPoInventory($batchLocation->batch->id);

                    $vendor_credit = $price_change * $qty_to_change_cost;

                    $out_record = new BatchLocation();
                    $out_record->batch_id = $batchLocationAggregate->batch_id;
                    $out_record->location_id = $batchLocationAggregate->location_id;
                    $out_record->unit_price = $current_price;
                    $out_record->quantity = ($qty_to_change_cost * -1);
                    $out_record->approved = 1;
                    $out_record->approved_at = Carbon::now();
                    $out_record->cost_change = 1;
                    $out_record->save();

                    $in_record = $out_record->replicate();
                    $in_record->quantity = $qty_to_change_cost;
                    $in_record->unit_price = $new_price;
                    $in_record->approved = 1;
                    $in_record->approved_at = Carbon::now();
                    $in_record->cost_change = 1;
                    $in_record->save();

                    $avg_unit_cost = $batch->average_unit_cost();
                    $batch->avg_unit_price = $avg_unit_cost;
                    $batch->subtotal_price = ($avg_unit_cost * $batch->units_purchased);
                    $batch->save();

                    if($batch->purchase_order_without_location_scope) {
                        $batch->purchase_order_without_location_scope->updateTotals();
                    }

                    $log_description = ($price_change < 0 ? 'Reduction' : 'Increase');
                    $activity_prop = collect([
                        'Batch ID' => $batch->id,
                        'SKU' => $batch->ref_number,
                        'Qty Changed' => $qty_to_change_cost,
                        'Cost' => display_currency($current_price),
                        'Cost Change' => display_currency($price_change),
                        'New Cost' => display_currency($request->get('new_unit_cost')),
                        'Location' => $batchLocationAggregate->location->name,
                        'Vendor Credit' => display_currency($vendor_credit),
                    ]);

                    activity('batch')
                        ->causedBy(Auth::user())
                        ->performedOn($batch)
                        ->withProperties($activity_prop)
                        ->log('Cost '.$log_description);

                    if($batch->purchase_order_without_location_scope) {
                        activity('purchase-order')
                            ->causedBy(Auth::user())
                            ->performedOn($batch->purchase_order_without_location_scope)
                            ->withProperties($activity_prop)
                            ->log('Cost '.$log_description);
                    }

                }

                $batchLocationAggregate->location_batch_name = $request->get('location_batch_name');
                $batchLocationAggregate->suggested_unit_sale_price = $request->get('suggested_unit_sale_price');
                $batchLocationAggregate->min_flex = $request->get('min_flex');
                $batchLocationAggregate->save();

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                flash()->error($e->getMessage());

                return redirect(route('batch-location-aggregate.edit', $batchLocationAggregate->id));
            }

            return redirect(route('batches.show', $batchLocationAggregate->batch_id));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\BatchLocationAggregate  $batchLocationAggregate
     * @return \Illuminate\Http\Response
     */
    public function destroy(BatchLocationAggregate $batchLocationAggregate)
    {
        //
    }
}
