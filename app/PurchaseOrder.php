<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/13/17
 * Time: 16:31
 */

namespace App;

use App\Events\POCreated;
use App\Scopes\PurchaseOrderLocationScope;
use App\Scopes\PurchaseOrderScope;
use App\Traits\ActivityLogTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Scottlaurent\Accounting\ModelTraits\AccountingJournal;
use Scottlaurent\Accounting\Services\Accounting;

class PurchaseOrder extends Order
{
    use AccountingJournal, ActivityLogTrait;

    protected $table = 'orders';

    protected $payment_type = 'paid';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new PurchaseOrderScope);
        static::addGlobalScope(new PurchaseOrderLocationScope);

//        static::created(function ($purchase_order) {
//            $purchase_order->initJournal();
//        });
    }

    /**
     * @return bool
     */
    public function getEditableAttribute()
    {
        $this->load('transactions');

        return $this->transactions->isEmpty() ? true : false;
    }

    /**
     * @return bool
     */
    public function getNotEditableAttribute()
    {
        return ! $this->editable;
    }

    public function getCanBeDeletedAttribute()
    {
        foreach ($this->batches as $batch) {
            if (bccomp($batch->units_purchased, $batch->inventory, 4) !== 0) {
                return false;
            }

            //if allocated quantity and quantity at location are NOT the same. CANNOT DELETE.
            $location_remaining_inventory = $batch->allocated_and_sold_inventory->groupBy('name');
            foreach ($batch->allocated_inventory->groupBy('name') as $location_name => $allocations) {
                if (bccomp($allocations->sum('batch_location.quantity'), $location_remaining_inventory[$location_name]->sum('batch_location.quantity')) !== 0) {
                    return false;
                }
            }
        }

        if ($this->trashed()) {
            return false;
        }

        return true;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function cultivator()
    {
        return $this->belongsTo(User::class, 'cultivator_id');
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    public function return_purchase_orders()
    {
        return $this->hasMany(ReturnPurchaseOrder::class, 'parent_id');
    }

    public function originating_entity()
    {
        return $this->belongsTo(User::class, 'bill_to_id');
    }

    public function getOriginatingEntityModelAttribute()
    {
        if (empty($this->originating_entity)) {
            return $this->vendor;
        }

        return $this->originating_entity;
    }

//    public function scopeVendorPayable($query)
//    {
//        $query = self::query();
//
//        $query->select([]);
//    }

    public function updateTotals()
    {
        $this->load('transactions');

        $this->subtotal = $this->batches->sum('subtotal_price');
        $this->total = $this->subtotal;
        $this->balance = $this->total - $this->transactions->sum('amount');

        $amount_change = ($this->subtotal - $this->getOriginal('subtotal'));

        if ($amount_change > 0) {

            $this->journal->credit($amount_change * 100);
            $this->vendor->journal->credit($amount_change * 100);

        } elseif ($amount_change < 0) {

            $this->journal->debit(abs($amount_change) * 100);
            $this->vendor->journal->debit(abs($amount_change * 100));

        }

        $this->journal->resetCurrentBalances();
        $this->vendor->journal->resetCurrentBalances();

        if ($this->balance) {
            $this->status = 'open';
        } elseif ($this->trashed()) {
            $this->status = 'voided';
        } else {
            $this->status = 'closed';
        }
        $this->save();
    }

    public function set_order_id()
    {
        $this->ref_number = $this->new_ref_number('PO');
        $this->save();

        return $this;
    }

    public static function app_search($q)
    {
        parent::$search_table = 'vendor';

        return parent::app_search($q);
    }

    public function getRemainingInventoryValueAttribute()
    {
        $inventory_value = 0;

        $this->batches->each(function ($batch, $b_key) use (&$inventory_value) {

            //inventory value @ nest
//            debug($batch->name);
            $inventory_value += (($batch->available_for_allocation) * $batch->unit_price);

            //get inventory value @ each location
            $inventory_value += $batch->locations->sum('batch_location.line_cost');

//            debug($batch->locations->sum('batch_location.line_cost'), $batch->locations);
            //get value of quantity that are on orders
            $batch->order_details->each(function ($order_detail) use (&$inventory_value) {
                if (! $order_detail->sale_order) {
                    return;
                }

                if ($order_detail->sale_order->status != 'delivered') {
                    $units = (! is_null($order_detail->units_accepted) ? $order_detail->units_accepted : $order_detail->units);
                    $inventory_value += ($units * $order_detail->unit_cost);
                }
            });
        });

        return $inventory_value;
    }

    public function loadLocationBalances()
    {
        $location_cost_owed = [];

//        dump($this);
        //dd($this->transactions->groupBy('location.name'));
        //collect payment by location
        foreach ($this->transactions->groupBy('location.name') as $location_name => $location_order_transactions) {
            if (Auth::user()->hasLocation() && $location_name != Auth::user()->current_location->name) {
                continue;
            }

            if(!$location_name) $location_name = 'Nest';

//            $filtered_location_order_transactions = $location_order_transactions->reject(function ($value) {
//                return !in_array($value->payment_method, ['Cash','Credit']);
//            });

            if ($location_order_transactions->count()) {
                $location_cost_owed[$location_name]['location_id'] = $location_order_transactions->first()->location_id;
                $location_cost_owed[$location_name]['transactions'] = $location_order_transactions;
                $location_cost_owed[$location_name]['total_paid'] = $location_order_transactions->sum('amount');
            }
        }

        // loop sold items - calculate cost of delivered, pending and remaining inventory
        foreach ($this->batches as $batch) {
            //dump($batch->id);
            if ($batch->allocated_and_sold_inventory->count()) {
                //dd($batch->allocated_and_sold_inventory->groupBy('name'));
                foreach ($batch->allocated_and_sold_inventory->groupBy('name') as $location_name => $batch_location) {

//                    debug($location_name, $batch_location);

                    if (empty($location_cost_owed[$location_name]['remaining_cost'])) {
                        $location_cost_owed[$location_name]['remaining_cost'] = 0;
                    }
                    $location_cost_owed[$location_name]['remaining_cost'] += $batch_location->sum('batch_location.line_cost');

                    if (empty($location_cost_owed[$location_name]['remaining_cost'])) {
                        $location_cost_owed[$location_name]['remaining_items'] = [];
                    }

                    if ($batch_location->sum('batch_location.quantity')) {
                        $batch_location->first()->batch_location->uom = $batch->uom;

                        $location_inventory = $batch_location->sum('batch_location.quantity');
                        $location_value = $batch_location->sum('batch_location.line_cost');
                        $unit_cost = ($location_inventory?($location_value/$location_inventory):0);

                        $location_cost_owed[$location_name]['remaining_items'][] = [
                            'batch_name' => $batch->name,
                            'batch_sku' => $batch->ref_number,
                            'uom' => $batch->uom,
                            'remaining_units' => $batch_location->sum('batch_location.quantity'),
                            'cost' => $unit_cost,
                            'subtotal_cost' => $batch_location->sum('batch_location.line_cost'),
                        ];
                    }
                }
            }

            foreach ($batch->transfer_logs as $transfer_log) {
                $location_name = ($transfer_log->location ? $transfer_log->location->name : 'Nest');

                if (empty($location_cost_owed[$location_name]['reconciled_cost'])) {
                    $location_cost_owed[$location_name]['reconciled_cost'] = 0;
                }
                $location_cost_owed[$location_name]['reconciled_cost'] += $transfer_log->inventory_loss;

                if (empty($location_cost_owed[$location_name]['reconciled_cost_details'])) {
                    $location_cost_owed[$location_name]['reconciled_cost_details'] = [];
                }

                $transfer_log->batch_id = $batch->id;
                $transfer_log->batch_name = $batch->name;
                $transfer_log->batch_sku = $batch->ref_number;
                $transfer_log->batch_uom = $batch->uom;

                $location_cost_owed[$location_name]['reconciled_cost_details'][] = $transfer_log;
            }

            foreach ($batch->order_details as $order_detail) {
                if (is_null($order_detail->sale_order)) {
                    continue;
                }

                $location_name = $order_detail->sale_order->location->name;
                $order_detail->batch_uom = $batch->uom;

                $location_cost_owed[$location_name]['location_id'] = $order_detail->sale_order->location->id;

                if (empty($location_cost_owed[$location_name]['total_paid'])) {
                    $location_cost_owed[$location_name]['total_paid'] = 0;
                }

                if (empty($location_cost_owed[$location_name]['pending_cost'])) {
                    $location_cost_owed[$location_name]['pending_cost'] = 0;
                    $location_cost_owed[$location_name]['pending_revenue'] = 0;
                    $location_cost_owed[$location_name]['pending_order_details'] = [];
                }

                if (empty($location_cost_owed[$location_name]['delivered_cost'])) {
                    $location_cost_owed[$location_name]['delivered_cost'] = 0;
                    $location_cost_owed[$location_name]['delivered_revenue'] = 0;
                    $location_cost_owed[$location_name]['delivered_order_details'] = [];
                }

                if ($order_detail->sale_order->status == 'delivered') {
                    $location_cost_owed[$location_name]['delivered_cost'] += $order_detail->cost;
                    $location_cost_owed[$location_name]['delivered_revenue'] += $order_detail->revenue;
                    $location_cost_owed[$location_name]['delivered_order_details'][] = $order_detail;
                } else {
                    $location_cost_owed[$location_name]['pending_cost'] += $order_detail->cost;
                    $location_cost_owed[$location_name]['pending_revenue'] += $order_detail->sale_price;
                    $location_cost_owed[$location_name]['pending_order_details'][] = $order_detail;
                }

                @$location_cost_owed[$location_name]['total_cost'] += $order_detail->cost;
            }
        }
        //dump($location_cost_owed);
        $this->location_cost_owed = collect($location_cost_owed);
        //dd($this->location_cost_owed);

        $po_owed = 0;

//        dump($this->location_cost_owed);
        $this->location_cost_owed->transform(function ($location_data, $location_name) use (&$po_owed) {
            try {
                $delivered_coat = ! empty($location_data['delivered_cost']) ? $location_data['delivered_cost'] : 0;
                $total_paid = ! empty($location_data['total_paid']) ? $location_data['total_paid'] : 0;
                $recon_cost = ! empty($location_data['reconciled_cost']) ? $location_data['reconciled_cost'] : 0;

                $location_data['total_owed'] = (float)bcsub(bcadd($delivered_coat,$recon_cost,2),$total_paid,2);

                $po_owed += $location_data['total_owed'];
            } catch (\Exception $e) {
//                dump($e->getMessage());
//                dd($this);
            }

            return $location_data;
        });

        $this->total_owed = $po_owed;

//        dd($po_owed);
    }

    public function applyPayment($amount, $txn_date, $payment_method = null, $ref_number = null, $memo = null, $txn_id = null, $location_id = null, $txn_fee = null, $parent_id = null, $type=null, $vendor_id=null)
    {

        $transaction = $this->journal->{($amount > 0 ? 'debit' : 'credit')}(abs($amount) * 100);

        //need to refresh to get the created primary key so it can be passed to applypayment
        $transaction->refresh();
        $this->journal->resetCurrentBalances();

        return parent::applyPayment($amount, $txn_date, $payment_method, $ref_number, $memo, $transaction->acct_journal_txn_pid, $location_id, $txn_fee, $parent_id, $type, $vendor_id); // TODO: Change the autogenerated stub
    }
}
