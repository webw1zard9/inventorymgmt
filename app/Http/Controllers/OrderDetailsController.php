<?php

namespace App\Http\Controllers;

use App\Batch;
use App\Events\BatchSoldOut;
use App\OrderDetail;
use App\SaleOrder;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderDetailsController extends Controller
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        try {
            DB::beginTransaction();

//            dd($data);
            $sale_order = SaleOrder::find($data['_sale_order_id']);

            $batch = Batch::find($data['batch_id']);

            $data['unit_sale_price'] = $data['_unit_sale_price'];
            //dd($data);
            if (empty($data['unit_sale_price'])) {
                throw new \Exception('Please enter a sales price.');
            }

            if (! empty($data['_sold_as_name_input'])) {
                $data['sold_as_name'] = $data['_sold_as_name_input'];
            }

            if ($data['units'] > $batch->available_for_sale) {
                throw new \Exception('Quantity exceeds available: '.$batch->available_for_sale.' '.$batch->uom);
            }

            $sale_order->addUpdateItem($batch, $data['_sold_as_name_input'], $data['units'], $data['unit_sale_price']);

            $sale_order->calculateTotals();

            flash()->success($data['_sold_as_name_input'].' added to order.');

            DB::commit();
        } catch (QueryException $e) {
            DB::rollBack();
            flash()->error('Unable to save item. '.$e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OrderDetail $orderDetail)
    {
        $data = $request->all();
        //dd($data);
        try {
            if (Auth::check() && ! Auth::user()->hasLocation()) {
                throw new \Exception('Must have a location selected for this action!');
            }

            DB::beginTransaction();

            if (Arr::has($data, 'cog') && $data['cog'] == 1) {
                if (isset($data['units'])) {

                    if ($data['units'] == 0) {
                        throw new \Exception('To delete an item, use the delete button.');
                    }

                    if (bccomp($orderDetail->units, $data['original_units']) !== 0) {
                        throw new \Exception('Error. Something changed, try again.');
                    }
                    //dump($orderDetail->units);
//                    dump();
//                    dd($data['original_units']);

                    $original_inventory = $orderDetail->batch->getOriginal('inventory');

                    $inventory_change = (float) bcsub($orderDetail->units, $data['units'], 4);

                    $orderDetail->batch->inventory = bcadd($orderDetail->batch->inventory, $inventory_change, 4);

                    if ((bccomp((-$inventory_change), $orderDetail->batch->available_for_sale, 4) > 0)) {
                        throw new \Exception('Updated quantity exceeds available '.$orderDetail->batch->available_for_sale.' '.$orderDetail->batch->uom);
                    }

                    if ($data['unit_sale_price'] < $orderDetail->batch->min_flex_price) {
                        throw new \Exception('Sale price is less than the minimum flex price.');
                    }

                    $orderDetail->batch->save();
                    $orderDetail->batch_location->quantity = ($data['units'] * -1);

                    if (($data['unit_sale_price'] >= $orderDetail->batch->suggested_unit_sale_price)
                        || Auth::user()->level() >= 60) {
                        $orderDetail->batch_location->price_approved = 1;
                    } else {
                        $orderDetail->batch_location->price_approved = 0;
                    }
                    $orderDetail->batch_location->save();
                }

                if (Arr::has($data, '_markup')) {
                    $data['unit_sale_price'] = bcadd($orderDetail->unit_cost, $data['_markup'], 2);
                }
            } else {
                $data['units_accepted'] = $data['units'];
            }

//        $data['subtotal_sale_price'] = $data['units'] * $orderDetail['unit_sale_price'];

            $orderDetail->cog = $request->get('cog');
            $orderDetail->sold_as_name = $request->get('sold_as_name');
            $orderDetail->units = $request->get('units');
            $orderDetail->unit_sale_price = $request->get('unit_sale_price');
            $orderDetail->units_accepted = null;
            $orderDetail->units_fulfilled = null;

            $original_name = $orderDetail->getOriginal('sold_as_name');
            $orignal_units = $orderDetail->getOriginal('units');
            $original_unit_sale_price = $orderDetail->getOriginal('unit_sale_price');

            $orderDetail->save();

            if ($changes = $orderDetail->getChanges()) {
                $activity_prop = collect([
                    'Batch ID' => $orderDetail->batch->id,
                    'SKU' => $orderDetail->batch->ref_number,
                ]);

                if (! empty($changes['sold_as_name'])) {
                    $activity_prop->put('Original Name', $original_name);
                    $activity_prop->put('New Name', $changes['sold_as_name']);
                }

                if (! empty($changes['units'])) {
                    $activity_prop->put('Original Qty', $orignal_units.' '.$orderDetail->batch->uom);
                    $activity_prop->put('New Qty', $changes['units'].' '.$orderDetail->batch->uom);
                }

                if (! empty($changes['unit_sale_price'])) {
                    $activity_prop->put('Original Unit Price', display_currency($original_unit_sale_price));
                    $activity_prop->put('New Unit Price', display_currency($changes['unit_sale_price'] / 100));
                }

                if (! $orderDetail->batch_location->price_approved) {
                    $activity_prop->put('Discount Requires Approval', 'Yes');
                }

                activity('sale-order')
                    ->causedBy(Auth::user())
                    ->performedOn($orderDetail->sale_order)
                    ->withProperties($activity_prop)
                    ->log('Item Updated');

                $activity_prop->put('Order#', $orderDetail->sale_order->ref_number);
                $activity_prop->put('Location', Auth::user()->current_location->name);
//dd($activity_prop);
                activity('batch')
                    ->causedBy(Auth::user())
                    ->performedOn($orderDetail->batch)
                    ->withProperties($activity_prop)
                    ->log('Updated on Order');
            }

            if (bccomp($original_inventory, 0.0, 4) === 0) {
                activity('batch')
                    ->causedBy(Auth::user())
                    ->performedOn($orderDetail->batch)
                    ->log('Back In Stock');
            }

            if (bccomp($orderDetail->batch->inventory, 0.0, 4) === 0) {
//                event(new BatchSoldOut($orderDetail->batch));
                activity('batch')
                    ->causedBy(Auth::user())
                    ->performedOn($orderDetail->batch)
                    ->log('Sold Out');
            }

            $orderDetail->sale_order->calculateTotals();

            DB::commit();

            flash()->success('Item updated');
        } catch (QueryException $e) {
            DB::rollBack();
            flash()->error('Unable to update item. '.$e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function retag(Request $request, OrderDetail $orderDetail)
    {

        /// add units back to original batch
//        $orderDetail->batch->inventory = bcadd($orderDetail->batch->inventory, $orderDetail->units, 4);
        $original_batch = $orderDetail->batch;

        /// retag original batch
        $uid = config('inventorymgmt.metrc_tag')[$original_batch->license_id].str_pad((int) $request->get('tag_id'), 9, 0, STR_PAD_LEFT);

        $qty_to_xfer = $orderDetail->units;
        $used_weight = ($original_batch->uom == 'g') ? $qty_to_xfer : $qty_to_xfer * config('inventorymgmt.uom.lb');

        //amount
        $amount = $orderDetail->units;
        $uom = $original_batch->uom;

        $packages_created = [
            [
                'ref_number' => $uid,
                'category_id' => $original_batch->category_id,
                'brand_id' => null,
                'amount' => $amount,
                'uom' => $uom,
                'packed_date' => Carbon::today(),
                'fund_id' => $original_batch->fund_id,
            ],
        ];

//            dump($packages_created);

        try {
            $new_batch = $original_batch->transfer(
                $used_weight,
                $qty_to_xfer,
                $packages_created
            );

            if ($new_batch instanceof Batch) {
                $new_batch->inventory = 0;
                $new_batch->save();

                $orderDetail->batch_id = $new_batch->id;
                $orderDetail->save();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());

            return redirect()->back();
        }

        flash()->success('Batch '.$original_batch->ref_number.' retagged to: '.$new_batch->ref_number);

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
