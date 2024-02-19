<?php

namespace App\Http\Livewire\SaleOrder;

use App\OrderDetail;
use App\SaleOrder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LineItem extends Component
{
    public OrderDetail $order_detail;
    public SaleOrder $sale_order;

    public $available_inventory = 0;

    protected $listeners = [
        'soStatusChanged' => '$refresh',
//        'addItemModalClosed' => '$refresh'
    ];

    protected $rules = [
        'order_detail.sold_as_name' => 'required',
        'order_detail.units' => '',
        'order_detail.unit_sale_price' => '',
        'order_detail.units_fulfilled' => ''
    ];

    protected $messages = [
        'order_detail.sold_as_name.required' => 'Name required',
    ];

    public function mount(OrderDetail $order_detail, SaleOrder $sale_order)
    {
        $this->order_detail = $order_detail;
        $this->sale_order = $sale_order;

//        dd($order_detail);
        if(!empty($order_detail->batch->locations_aggregate)) {
            if($order_detail->batch->locations_aggregate->count() > 1) {
                $order_detail->batch->locations_aggregate = $order_detail->batch->locations_aggregate->filter(function($loc_agg) use ($sale_order) {
                    return $loc_agg->id == $sale_order->location_id;
                });
            }

            $this->available_inventory = $order_detail->batch->locations_aggregate->first()->batch_location_aggregate->available_inventory;
        }

//        $this->original_units = $order_detail->units;

//        $this->sold_as_name = $order_detail->sold_as_name;
//        $this->units = $order_detail->units;

//        $this->unit_sale_price = $order_detail->unit_sale_price;
//        $this->units_fulfilled = $order_detail->units_fulfilled;
    }

    public function render()
    {
        return view('livewire.sale-order.line-item');
    }

    public function update()
    {

        $this->validate();

        try {
            if($this->order_detail->sale_order->location->trashed()) {
                throw new \Exception("Unable to edit from order. Location ".$this->order_detail->sale_order->location->name." is no longer active.");
            }

            if (Gate::denies('batches.sell')) {
                throw new \Exception('Permission denied');
            }

            DB::beginTransaction();

            $this->resetErrorBag();

            if (!$this->order_detail->units || $this->order_detail->units <= 0) {
                $this->order_detail->units = $this->order_detail->getOriginal('units');
                throw new \Exception('To delete an item, use the delete button.');
            }

            //load batches inventory values for this location
            $this->order_detail->batch->load(['locations_aggregate' => function ($q) {
                $q->where('location_id', $this->order_detail->sale_order->location_id);
            }]);

//            dump($this->order_detail->batch->getOriginal('inventory'));
//            dd($this->order_detail->batch);

//            $original_inventory = $this->order_detail->batch->getOriginal('inventory');
            $original_inventory = $this->order_detail->batch->locations_aggregate->first()->batch_location_aggregate->available_inventory;

            $price_change = (float) bcsub($this->order_detail->getOriginal('unit_sale_price'), $this->order_detail->unit_sale_price, 4);
            $inventory_change = (float) bcsub($this->order_detail->getOriginal('units'), $this->order_detail->units, 4);

//            $batch_location_inventory = $this->order_detail->batch->getLocationInventoryValues($this->sale_order->location_id);

            if ($this->order_detail->batch->track_inventory && (bccomp(($inventory_change*-1), $original_inventory, 4) > 0)) {
                $change_to_unit = $this->order_detail->units;
                $this->order_detail->units = $this->order_detail->getOriginal('units');
                throw new \Exception('Unable to update quantity to '.$change_to_unit.' '.$this->order_detail->batch->uom.'. Exceeds '.$original_inventory.' '.$this->order_detail->batch->uom." available");
            }



            if ($this->order_detail->unit_sale_price < $this->order_detail->batch->min_flex_price) {
                $change_to_price = $this->order_detail->unit_sale_price;
                $this->order_detail->unit_sale_price = $this->order_detail->getOriginal('unit_sale_price');
                throw new \Exception(display_currency($change_to_price).' is less than the minimum flex price allowed.');
            }

//            if($this->order_detail->batch->track_inventory) {
//                $this->order_detail->batch->inventory = bcadd($this->order_detail->batch->inventory, $inventory_change, 4);
//            }
//            $this->order_detail->batch->save();

            if($this->order_detail->batch_location) { //will only have allocation if tracking inventory...
                $this->order_detail->batch_location->quantity = ($this->order_detail->units * -1);

                if (($this->order_detail->unit_sale_price >= $this->order_detail->batch->suggested_unit_sale_price) || Auth::user()->level() >= 60) {
                    $this->order_detail->batch_location->price_approved = 1;
                } else {
                    $this->order_detail->batch_location->price_approved = 0;
                    $this->order_detail->sale_order->status = 'hold';
                }
                $this->order_detail->batch_location->save();
            }


            ///** updated sale order journal */

            $original_subtotal = ($this->order_detail->getOriginal('units') * $this->order_detail->getOriginal('unit_sale_price'));
            $new_subtotal = ($this->order_detail->units * $this->order_detail->unit_sale_price);

            $subtotal_change = bcsub($new_subtotal, $original_subtotal, 2);

            if($this->order_detail->sale_order->discount && $this->order_detail->sale_order->discount_type == 'perc') {
                $this->order_detail->sale_order->discount = $new_subtotal * ($this->order_detail->sale_order->discount_applied / 100);
                $subtotal_change = bcsub($subtotal_change, ($subtotal_change * $this->order_detail->sale_order->discount_applied / 100), 2);
            }

            $this->order_detail->sale_order->save();


//            dd($subtotal_change);
            $subtotal_cents = convert_to_cents($subtotal_change);
            if($subtotal_change > 0) {
                $this->order_detail->sale_order->journal->credit($subtotal_cents);
            } elseif($subtotal_change < 0) {
                $this->order_detail->sale_order->journal->debit($subtotal_cents * -1);
            }
            $this->order_detail->sale_order->journal->resetCurrentBalances();

            //*************************//

            //if fullfilled units are greater than ordered reset fulfilled value.
            if(bccomp($this->order_detail->units_fulfilled, $this->order_detail->units)===1) {
                $this->order_detail->units_fulfilled = null;
            }

            $original_name = $this->order_detail->getOriginal('sold_as_name');
            $orignal_units = $this->order_detail->getOriginal('units');
            $original_unit_sale_price = $this->order_detail->getOriginal('unit_sale_price');

            $this->order_detail->save();

            $this->order_detail->sale_order->calculateTotals();

//            if($this->order_detail->sale_order->discount && $this->order_detail->sale_order->discount_type == 'perc') {
//                $this->order_detail->sale_order->recalculatePctDiscount();
//            }

//            dd($this->order_detail->sale_order->balance);

            if($this->order_detail->sale_order->balance < 0) {
                $this->order_detail->sale_order->removeDiscount();
            }

            if ($changes = $this->order_detail->getChanges()) {
                $activity_prop = collect([
                    'Batch ID' => $this->order_detail->batch->id,
                    'SKU' => $this->order_detail->batch->ref_number,
                    'Name' => $this->order_detail->sold_as_name,
                ]);

                if (! empty($changes['sold_as_name'])) {
                    $activity_prop->put('Original Name', $original_name);
                    $activity_prop->put('New Name', $changes['sold_as_name']);
                }

                if (! empty($changes['units'])) {
                    $activity_prop->put('Original Qty', $orignal_units.' '.$this->order_detail->batch->uom);
                    $activity_prop->put('New Qty', $changes['units'].' '.$this->order_detail->batch->uom);
                }

                if (! empty($changes['unit_sale_price'])) {
                    $activity_prop->put('Original Unit Price', display_currency($original_unit_sale_price));
                    $activity_prop->put('New Unit Price', display_currency($changes['unit_sale_price'] / 100));
                }

                if ($this->order_detail->batch_location && !$this->order_detail->batch_location->price_approved) {
                    $activity_prop->put('Discount Requires Approval', 'Yes');
                }

                activity('sale-order')
                    ->causedBy(Auth::user())
                    ->performedOn($this->order_detail->sale_order)
                    ->withProperties($activity_prop)
                    ->log('Item Updated');

                $activity_prop->put('Order#', $this->order_detail->sale_order->ref_number);
                $activity_prop->put('Location', Auth::user()->current_location->name);

                activity('batch')
                    ->causedBy(Auth::user())
                    ->performedOn($this->order_detail->batch)
                    ->withProperties($activity_prop)
                    ->log('Updated on Order');
            }

            if (bccomp($original_inventory, 0.0, 4) === 0) {
                activity('batch')
                    ->causedBy(Auth::user())
                    ->performedOn($this->order_detail->batch)
                    ->log('Back In Stock');
            }

            if (bccomp($this->order_detail->batch->inventory, 0.0, 4) === 0) {
                activity('batch')
                    ->causedBy(Auth::user())
                    ->performedOn($this->order_detail->batch)
                    ->log('Sold Out');
            }

//            $this->original_units = $this->order_detail->units;
//            $this->order_detail->sale_order->calculateTotals();

            DB::commit();

            session()->flash('od-success', '');

            $this->dispatch('orderDetailUpdated');
//            $this->emit('refresh-add-batch-item');

        } catch (QueryException $e) {
            DB::rollBack();
            $this->addError('od-error', 'Unable to update item. ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('od-error', $e->getMessage());
        }
    }

    public function fulfillItem()
    {

        try {
            if($this->order_detail->sale_order->location->trashed()) {
                throw new \Exception("Unable to fulfill from order. Location ".$this->order_detail->sale_order->location->name." is no longer active.");
            }

            if((float)$this->order_detail->units_fulfilled < 0) {
                throw new \Exception('Must be greater than 0');
            }

            // || (bccomp(abs((float)$this->order_detail->batch_location->quantity), (float)$this->order_detail->units_fulfilled, 4) < 0)
            if (bccomp((float)$this->order_detail->units_fulfilled, (float)$this->order_detail->units) > 0) {
                $this->order_detail->units_fulfilled = $this->order_detail->getOriginal('units_fulfilled');
                throw new \Exception('Cannot fulfill an amount greater than ordered!');
            }

            if(strlen(trim($this->order_detail->units_fulfilled))==0) {
                $this->order_detail->units_fulfilled=null;
            } elseif((float)$this->order_detail->units_fulfilled==0) {
                $this->order_detail->units_fulfilled=0;
            }

            $this->order_detail->push();

            if ($this->order_detail->getChanges()) {
                $activity_prop = collect([
                    'Batch ID' => $this->order_detail->batch->id,
                    'SKU' => $this->order_detail->batch->ref_number,
                    'Name' => $this->order_detail->sold_as_name,
                    'Ordered Qty' => $this->order_detail->units.' '.$this->order_detail->batch->uom,
                    'Fulfilled Qty' => (is_null($this->order_detail->units_fulfilled) ? 'NULL' : $this->order_detail->units_fulfilled.' '.$this->order_detail->batch->uom),
                ]);

                activity('sale-order')
                    ->withProperties($activity_prop)
                    ->causedBy(Auth::user())
                    ->performedOn($this->order_detail->sale_order)
                    ->log('Fulfill Item');

                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($this->order_detail)
                    ->log('Fulfill Item');
            }

            DB::commit();

            if ($this->order_detail->sale_order->order_details_not_fully_fulfilled()->count() == 0) {
                $this->order_detail->sale_order->ready_for_delivery();
                session()->flash('success-message','All items fullfilled. Order ready for delivery!');
                return redirect(route('sale-orders.show', $this->order_detail->sale_order));

            } elseif($this->order_detail->sale_order->order_details_not_fulfilled()->count() == 0 || is_null($this->order_detail->units_fulfilled)) {
                $this->emit('allItemsFulfilled');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('od-error', $e->getMessage());
        }
    }

    public function removeItem()
    {
        try {

            DB::beginTransaction();

            if($this->order_detail->sale_order->location->trashed()) {
                throw new \Exception("Unable to delete from order. Location ".$this->order_detail->sale_order->location->name." is no longer active.");
            }

            $this->order_detail->sale_order->removeItem($this->order_detail);

            if($this->order_detail->sale_order->order_details->count() === 0 || $this->order_detail->sale_order->balance < 0) {
                $this->order_detail->sale_order->removeDiscount();
                session()->flash('error-message', "Discount removed. Can't be greater than subtotal.");
            }

//            if($this->order_detail->sale_order->discount && $this->order_detail->sale_order->discount_type == 'perc') {
//                $this->order_detail->sale_order->recalculatePctDiscount();
//            }

            DB::commit();

            $this->dispatch('orderDetailUpdated');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('od-error', $e->getMessage());
        }
    }

}
