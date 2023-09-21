<?php

namespace App\Http\Livewire\SaleOrder;

use App\Batch;
use App\SaleOrder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AddItemBatch extends Component
{
    public $batch;
    public SaleOrder $sale_order;

    public $category_name = '';
    public $brand_name = '';
    public $quantity = '';
    public $sold_as_name;
    public $unit_cost;
    public $available_qty;
    public $qty_on_order = 0;
    public $price;

    protected $listeners = [];

    protected $rules = [
        'quantity' => 'required',
        'price' => 'required',
    ];

    protected $rules_messages = [
        'quantity.required' => 'Required',
        'price.required' => 'Required',
    ];

    public function mount($batch, SaleOrder $sale_order)
    {
//        debug(__CLASS__.' mounted!! '.$this->batch->id);
//        debug('mount add item batch comp');
        $this->batch = $batch;

//        debug($this->batch->available_inventory);

        $this->sale_order = $sale_order;

        $this->qty_on_order = $this->batch->order_detail_units;
        $this->category_name = $this->batch->cat_name;
        $this->brand_name = $this->batch->brand_name;

//        $this->loadProperties();

    }

    public function hydrate()
    {
//        dump('hydate');
//        dd($this->batch);
    }

    public function render()
    {

        $this->loadProperties();
//        debug(__CLASS__.' rendered!! ');
        return view('livewire.sale-order.add-item-batch');
    }

    public function addToOrder($quantity=null)
    {
        if (Gate::denies('batches.sell')) {
            return redirect(route('sale-orders.show', $this->sale_order));
        }

        if(!$this->sale_order->canAddItems()) {
            session()->flash('error-message', "Unable to add item, try again!");
            return redirect(route('sale-orders.show', $this->sale_order));
        }

        //load batches inventory values for this location
        $this->batch->load(['locations_aggregate' => function ($q) {
            $q->where('location_id', $this->sale_order->location_id);
        }]);

        if($quantity) {
            $this->quantity = $quantity;
        }

        $this->validate($this->rules, $this->rules_messages);

        try {
            DB::beginTransaction();

            if($this->batch->track_inventory && ($this->quantity > $this->batch->available_for_sale)) {
                throw new \Exception('Quantity exceeds available: '.$this->batch->available_for_sale.' '.$this->batch->uom);
            }

            $this->sale_order->addUpdateItem($this->batch, $this->unit_cost, $this->sold_as_name, $this->quantity, $this->price);
            $this->sale_order->calculateTotals();

//            if($this->sale_order->discount && $this->sale_order->discount_type == 'perc') {
//                $this->sale_order->recalculatePctDiscount();
//            }

            $this->batch->refresh();
            $this->qty_on_order += $this->quantity;
            $this->available_qty = $this->batch->available_for_sale;
            $this->reset('quantity');

            DB::commit();

        } catch (QueryException $e) {
            DB::rollBack();
            $this->addError('add-batch-error', 'Unable to add item. '.$e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('add-batch-error', $e->getMessage());
        }
    }

    protected function loadProperties()
    {
        debug("relation loaded: locations_aggregate");
        debug($this->batch->relationLoaded('locations_aggregate'));
        debug($this->batch->location_batch_name);

        if($this->batch->relationLoaded('locations_aggregate')) {
            debug('has relation');
            $batch_location_inventory = $this->batch->getLocationInventoryValues($this->sale_order->location_id);

            $this->sold_as_name = $batch_location_inventory->location_batch_name;
            $this->price = display_currency_no_sign($batch_location_inventory->suggested_unit_sale_price);
            $this->available_qty = $batch_location_inventory->available_inventory;
            $this->unit_cost = $batch_location_inventory->location_unit_price?:$this->batch->unit_price;
        } else {

            $this->sold_as_name = $this->batch->location_batch_name;
            $this->price = display_currency_no_sign($this->batch->suggested_unit_sale_price);
            $this->available_qty = $this->batch->available_inventory;
            $this->unit_cost = $this->batch->location_unit_price?:$this->batch->unit_price;
        }

    }

}
