<?php

namespace App\Http\Controllers;

use App\Batch;
use App\BatchLocation;
use App\Filters\BatchIntakeFilters;
use App\Location;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BatchLocationController extends Controller
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
     * @param  \App\BatchLocation  $batchLocation
     * @return \Illuminate\Http\Response
     */
    public function show(BatchLocation $batchLocation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\BatchLocation  $batchLocation
     * @return \Illuminate\Http\Response
     */
    public function edit(BatchLocation $batchLocation)
    {
        view()->share('title', 'Edit Allocation');

        $batchLocation->load('location', 'batch.category');
//        dd($batchLocation);

        $batchBuilder = $batchLocation->batch();
        if (Auth::user()->hasLocation()) {
            $batchBuilder->currentLocationUnitPrice();
        }
        $batch = $batchBuilder->first();

        $location = $batchLocation->location;
//dd($batch);
        return view('batch_location.edit', compact('batchLocation', 'batch', 'location'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\BatchLocation  $batchLocation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BatchLocation $batchLocation)
    {
        if ($request->has('approved')) {
            $request->request->add(['approved_at' => Carbon::now()]);
            $batchLocation->update($request->only(['approved', 'approved_at']));

            $this->approveAllocationActivityLog($batchLocation);

            flash()->success($batchLocation->name.' approved! It will appear in your inventory now.');

            return back();
        }
    }

    public function approveAllIntake(Request $request, BatchIntakeFilters $batchIntakeFilters)
    {
        if ($request->expectsJson()) {
            $batch_location_ids = collect($request->all())->pluck('value');

            $batch_allocations = BatchLocation::whereIn('id', $batch_location_ids)->get();

            $batch_allocations->each(function ($batch_location) {
                $batch_location->update([
                    'approved' => 1,
                    'approved_at' => Carbon::now(),
                ]);

                $this->approveAllocationActivityLog($batch_location);
            });

            $intake_batch_locations = BatchLocation::needIntakeApproval($batchIntakeFilters)->get();

            return response()->json([
                'ids' => $batch_location_ids,
                'count' => $intake_batch_locations->count(),
            ]);
        }
    }

    public function rejectAllIntake(Request $request, BatchIntakeFilters $batchIntakeFilters)
    {
        if ($request->expectsJson()) {
            $batch_location_ids = collect($request->all())->pluck('value');

            $batch_allocations = BatchLocation::whereIn('id', $batch_location_ids)->get();

            $batch_allocations->each(function ($batch_location) {
                $this->deleteAllocation($batch_location);
            });

            $intake_batch_locations = BatchLocation::needIntakeApproval($batchIntakeFilters)->get();

            return response()->json([
                'ids' => $batch_location_ids,
                'count' => $intake_batch_locations->count(),
            ]);

            return response()->json($batch_location_ids);
        }
    }

    public function approveDiscount(Request $request, BatchLocation $batchLocation)
    {
        try {
            DB::beginTransaction();
            $batchLocation->price_approved = 1;
            $batchLocation->save();

            $activity_prop = collect([
                'Batch ID' => $batchLocation->order_detail->batch->id,
                'SKU' => $batchLocation->order_detail->batch->ref_number,
                'Name' => $batchLocation->order_detail->sold_as_name,
                'Suggested Sale Price' => display_currency($batchLocation->order_detail->batch->suggested_unit_sale_price),
                'Price Approved' => display_currency($batchLocation->order_detail->unit_sale_price),
            ]);

            activity('sale-order')
                ->causedBy(Auth::user())
                ->performedOn($batchLocation->order_detail->sale_order)
                ->withProperties($activity_prop)
                ->log('Line Discount Approved');

            flash()->success('Discount approved!');
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return redirect(route('sale-orders.discount-approval'));
    }

    public function rejectDiscount(Request $request, BatchLocation $batchLocation)
    {
        try {
            DB::beginTransaction();

            $data = [
                'batch_location_id' => $batchLocation->id,
                'suggested_unit_sale_price' => $batchLocation->order_detail->batch->suggested_unit_sale_price,
                'requested_unit_sale_price' => $batchLocation->order_detail->unit_sale_price,
            ];

            $batchLocation->price_approved = 1;
            $batchLocation->order_detail->unit_sale_price = $batchLocation->order_detail->batch->suggested_unit_sale_price;
            $batchLocation->push();

            $batchLocation->order_detail->sale_order->calculateTotals();

            $activity_prop = collect([
                'Batch ID' => $batchLocation->order_detail->batch->id,
                'SKU' => $batchLocation->order_detail->batch->ref_number,
                'Name' => $batchLocation->order_detail->sold_as_name,
                'Suggested Sale Price' => display_currency($batchLocation->order_detail->batch->suggested_unit_sale_price),
                'Price Rejected' => display_currency($batchLocation->order_detail->unit_sale_price),
            ]);

            activity('sale-order')
                ->causedBy(Auth::user())
                ->performedOn($batchLocation->order_detail->sale_order)
                ->withProperties($activity_prop)
                ->log('Line Discount Rejected');

            flash()->error('Discount rejected!');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return redirect(route('sale-orders.discount-approval'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\BatchLocation  $batchLocation
     * @return \Illuminate\Http\Response
     */
    public function destroy(BatchLocation $batchLocation)
    {
        $redir = request('redir');

        try {
            DB::beginTransaction();

            $this->deleteAllocation($batchLocation);

            DB::commit();

            flash()->success('Item rejected!');
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return redirect($redir ?: route('batches.intake'));
    }

    protected function deleteAllocation(BatchLocation $batchLocation)
    {
        if ($batchLocation->parent_batch_location) {
            $BL = $batchLocation->parent_batch_location;
        } else {
            $BL = $batchLocation;
        }

        $BL->delete();

        $batch = $BL->batch;

        $activity_prop = collect([
            'Location' => Location::find($BL->location_id)->name,
            'Qty' => $BL->quantity.' '.$batch->uom,
            'Name' => $BL->name,
            'Unit Price' => display_currency($BL->unit_price),
            'Suggested Unit Sale Price' => display_currency($BL->suggested_unit_sale_price),
            'Min Flex' => display_currency($BL->min_flex),
        ]);

        activity('batch')
            ->causedBy(Auth::user())
            ->performedOn($batch)
            ->withProperties($activity_prop)
            ->log('Allocation Rejected');

        return true;
    }

    protected function approveAllocationActivityLog($batch_location)
    {
        activity('inventory-intake-approved')
            ->causedBy(Auth::user())
            ->performedOn($batch_location)
            ->withProperty('batch_location_id', $batch_location->id)
            ->log('Inventory intake.');

        $batch = $batch_location->batch;

        $activity_prop = collect([
            'Location' => Location::find($batch_location->location_id)->name,
            'Qty' => $batch_location->quantity.' '.$batch->uom,
            'Name' => $batch_location->name,
            'Unit Price' => display_currency($batch_location->unit_price),
            'Suggested Unit Sale Price' => display_currency($batch_location->suggested_unit_sale_price),
            'Min Flex' => display_currency($batch_location->min_flex),
        ]);

        activity('batch')
            ->causedBy(Auth::user())
            ->performedOn($batch)
            ->withProperties($activity_prop)
            ->log('Allocation Approved');
    }
}
