<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/20/17
 * Time: 17:51
 */

namespace App;

use App\Scopes\SaleOrderScope;
use App\Scopes\UserOrderScope;
use App\Traits\ActivityLogTrait;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Scottlaurent\Accounting\ModelTraits\AccountingJournal;

class SaleOrder extends Order
{
    use AccountingJournal, ActivityLogTrait;

    protected $table = 'orders';

    protected $payment_type = 'payment';

    protected $sales_comm;

//    protected $total_units_sold = [];
    protected $total_grams_sold = [];

    protected $total_lbs_sold = 0;

    protected $units_purchased = [];

    public $latest_order_detail = null;

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new SaleOrderScope);
        static::addGlobalScope(new UserOrderScope);
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->type = 'sale';
    }
    public function return_orders()
    {
        return $this->hasMany(SaleOrder::class, 'parent_id');
    }

    public function parent_order()
    {
        return $this->belongsTo(SaleOrder::class, 'parent_id');
    }

    public function order_details_not_fulfilled()
    {
        return $this->order_details()->where(function ($q) {
            $q->whereNull('units_fulfilled');
        });
    }

    public function order_details_not_fully_fulfilled()
    {
        return $this->order_details()->where(function ($q) {
            $q->whereColumn('units', '!=', 'units_fulfilled')->orWhereNull('units_fulfilled');
        });
    }

    public function scopeRequiresDiscountApproval($builder)
    {
        return $builder->where('discount_approved', 0);
    }

    public function scopeRequiresDiscountLineApproval($builder)
    {
        return $builder->whereHas('order_details.batch_location', function ($q) {
            $q->where('price_approved', 0);
        })
            ->with(['order_details.batch_location' => function ($q) {
                $q->where('price_approved', 0)
                    ->with([
                        'location',
                        'batch',
                        'order_detail.batch',
                        'order_detail.sale_order.customer',
                        'order_detail.sale_order.sales_rep',
                    ]);
            }]);
    }

    public function scopeWithDiscountDetails($builder, $date_range, $location_id)
    {
        return $builder->withDateRange($date_range)
            ->where('discount', '!=', 0)
            ->whereLocation($location_id)
            ->with('location','sales_rep', 'customer', 'user')
            ->orderBy('location_id')
            ->orderBy('delivered_at');
    }

    public function scopeWhereLocation($builder, $location_id)
    {
        if($location_id) {
            return $builder->where('location_id', $location_id);
        }
        return $builder;
    }

    public function getSubtotalCalculatedAttribute($value)
    {
        return $this->order_details->sum('line_item_subtotal');
    }

    public function getSubtotalAfterDiscountAttribute($value)
    {
        return $this->subtotal - $this->discount;
    }

    public function getTotalCalculatedAttribute($value)
    {
        return $this->order_details->sum('line_item_subtotal') - $this->discount;
    }

    /**
     * @param $value
     * @return float
     */
    public function getExciseTaxAttribute($value)
    {
        return $value / 100;
    }

    public function getCostByFundAttribute()
    {
        return $this->order_details_cog->groupBy(function ($order_detail_cog) {
            return $order_detail_cog->batch->fund->name;
        });
    }

    public function getCostAttribute()
    {
        return $this->order_details_cog->sum('cost');
    }

    public function getRevenueAttribute()
    {
        return $this->order_details_cog->sum('revenue') - $this->discount;
    }

    public function getGrossProfitAttribute()
    {
        if (! $this->hasRevenue() || $this->status != 'delivered') {
            return 0;
        }

        return ($this->revenue - $this->discount) - $this->cost;
    }

    public function getMarginAttribute()
    {
//        $subtotal = ($this->hasDiscount()? $this->subtotal - $this->discount : $this->subtotal );
//        $subtotal = ($this->hasDiscount()?  : $this->subtotal );
        if (! $this->hasRevenue()) {
            return 0;
        }

        return ($this->revenue - $this->discount) - $this->cost;
    }

    public function getMarginPctAttribute()
    {
        if ($this->subtotal == 0 || $this->margin == 0) {
            return 0;
        }

        return number_format((($this->margin / $this->subtotal) * 100), 2);
    }

    public function getBatchesThatRequireRetagAttribute()
    {
        return collect();

        $od_retags = OrderDetail::select('batch_id', DB::raw('count(batch_id) as order_count'))
            ->whereIn('batch_id', $this->order_details->pluck('batch_id'))
            ->groupBy('batch_id')
            ->get()
            ->keyBy('batch_id');

        foreach ($this->order_details as $order_detail) {
            if (is_null($order_detail->batch)) {
                continue;
            }
            if ($od_retags->get($order_detail->batch->id)->order_count == 1 && $order_detail->batch->inventory == 0) {
                $od_retags->forget($order_detail->batch->id);
            }
        }

        return $od_retags;
    }

    public function hasRevenue()
    {
        return in_array($this->sale_type, ['co-pack', 'promotional', 'transfer']) ? false : true;
    }

    public function addUpdateItem($batch, $unit_cost, $sold_as_name, $quantity, $sale_price)
    {
        if (! $quantity) {
            throw new \Exception('Quantity required!');
        }

        if ($batch) {

            if ($sale_price < $batch->min_flex_price) {
                throw new \Exception('Sale price is less than the minimum flex price.');
            }

            if ($order_detail = $this->getOrderDetail($batch->id, $unit_cost, $sale_price, $sold_as_name)) {

                $price_approved = $order_detail->batch_location->price_approved;
                $order_detail->units = bcadd($order_detail->units, $quantity, 4);
                if($order_detail->batch_location) {
                    $order_detail->batch_location->quantity = ($order_detail->units * -1);
                }
                $order_detail->push();
            } else { //create new

                $order_detail = new OrderDetail();
                $order_detail->batch_id = $batch->id;
                $order_detail->sold_as_name = $sold_as_name;
                $order_detail->units = $quantity;
                $order_detail->unit_cost = $unit_cost;
                $order_detail->unit_sale_price = $sale_price;

                $this->order_details()->save($order_detail);

                $price_approved = (($sale_price < $batch->suggested_unit_sale_price) && Auth::user()->hasRole('salesrep') ? 0 : 1);

                if($batch->track_inventory) {
                    $batch->locations()->attach([[
                        'order_detail_id' => $order_detail->id,
                        'location_id' => $this->location_id,
                        'quantity' => ($quantity * -1),
                        'unit_price' => $unit_cost,
                        'name' => $sold_as_name,
                        'approved' => 1,
                        'price_approved' => $price_approved,
                    ]]);
                    $batch->loadMissing('locations');
                }

            }

            $this->journal->credit(($sale_price * $quantity) * 100);
            $this->journal->resetCurrentBalances();

            $activity_prop = collect([
                'Batch ID' => $batch->id,
                'SKU' => $batch->ref_number,
                'Name' => $sold_as_name,
                'Qty' => $quantity.' '.$batch->uom,
                'Sale Price' => display_currency($sale_price),
                'Discount Requires Approval' => ($price_approved ? 'No' : 'Yes'),
            ]);

            activity('sale-order')
                ->causedBy(Auth::user())
                ->performedOn($this)
                ->withProperties($activity_prop)
                ->log('Item Added');

            $activity_prop->put('Order#', $this->ref_number);
            $activity_prop->put('Location', Auth::user()->current_location->name);

            activity('batch')
                ->causedBy(Auth::user())
                ->performedOn($batch)
                ->withProperties($activity_prop)
                ->log('Added to Order');

            if($batch->track_inventory) {
                $batch->inventory = bcsub($batch->inventory, $quantity, 4);
                $batch->save();

                if (bccomp($batch->inventory, 0.0, 4) === 0) {
//                event(new BatchSoldOut($orderDetail->batch));
                    activity('batch')
                        ->causedBy(Auth::user())
                        ->performedOn($batch)
                        ->log('Sold Out');
                }
            }

        } else {
            $order_detail = new OrderDetail();
            $order_detail->sold_as_name = $sold_as_name;
            $order_detail->units = $quantity;
            $order_detail->unit_cost = 0;
            $order_detail->unit_sale_price = $sale_price;
            $order_detail->cog = 0;

            //            $order_detail->subtotal_sale_price = $quantity * $sale_price;

            $this->order_details()->save($order_detail);
        }

        $this->load('order_details');

        $this->latest_order_detail = $order_detail;

        return $this;
    }

    public function calculateTotals()
    {
        if (! $this->journal) {
            $this->initJournal();
        }

        $this->refresh();

        $this->subtotal = $this->subtotal_calculated;
        $this->total = ($this->subtotal - $this->discount);
        $this->save();

        $so_balance = floatval(bcsub($this->total, $this->transactions->sum('amount')));

        $comparison = bccomp($this->balance, $so_balance);

        if($comparison != 0) { //journal balance and order are out of sync

            if($comparison == 1) { //journal balance is > than order -- debit journal for the difference
                $amt = bcsub($this->balance, $so_balance) * 100;
                $this->journal->debit($amt);
                Bugsnag::notifyException(new \Exception($this->ref_number.": balance out of sync. DEBIT: ".$amt/100));

            } elseif($comparison == -1) {
                $amt = bcsub($this->balance, $so_balance) * -100;
                $this->journal->credit($amt);
                Bugsnag::notifyException(new \Exception($this->ref_number.": balance out of sync. CREDIT: ".$amt/100));

            }

        }

        $this->customer->journal->resetCurrentBalances();
        $this->journal->resetCurrentBalances();

        return $this;
    }

    public function removeItem(OrderDetail $orderDetail)
    {
        $batch = $orderDetail->batch;
        $batch_location = $orderDetail->batch_location;

        if($batch_location) {
            $batch_location->delete(); //this will trigger mysql:delete_batch_location_trigger
        }
        $orderDetail->delete();

        $this->journal->debit(($orderDetail->units * $orderDetail->unit_sale_price) * 100);
        $this->journal->resetCurrentBalances();

        $this->calculateTotals();

//        $this->recalculatePctDiscount();

        if ($batch->track_inventory) {
            $batch->inventory += (! is_null($orderDetail->units_accepted) ? $orderDetail->units_accepted : $orderDetail->units);
            $batch->save();
        }

//        BatchLocation::where('order_detail_id', $orderDetail->id)->delete();

        $activity_prop = collect([
            'Batch ID' => $orderDetail->batch->id,
            'SKU' => $orderDetail->batch->ref_number,
            'Name' => $orderDetail->sold_as_name,
            'Qty' => $orderDetail->units,
            'Price' => display_currency($orderDetail->unit_sale_price),
        ]);

        activity('sale-order')
            ->causedBy(Auth::user())
            ->performedOn($orderDetail->sale_order)
            ->withProperties($activity_prop)
            ->log('Item Deleted');

        $activity_prop->put('Order#', $orderDetail->sale_order->ref_number);
        $activity_prop->put('Location', $orderDetail->sale_order->location->name);

        activity('batch')
            ->causedBy(Auth::user())
            ->performedOn($orderDetail->batch)
            ->withProperties($activity_prop)
            ->log('Removed from Order');

    }

    public function hold()
    {

        $this->log_status_activity('Hold', ['Reverse'=>'']);

        $this->status = 'hold';
//        $this->delivered_at = null;
        $this->save();

        return $this;
    }

    public function open()
    {
        $this->status = 'open';
        $this->save();

        return $this;
    }

    public function in_transit()
    {
        $this->status = 'in-transit';
        $this->save();

        return $this;
    }

    public function ready_to_pack()
    {
        $this->log_status_activity('Ready To Pack');

        $this->status = 'ready to pack';
        $this->save();

        return $this;
    }

    public function ready_to_deliver()
    {
        $this->log_status_activity('Ready To Deliver');

        $this->status = 'ready to deliver';
        $this->save();

        return $this;
    }

    public function ready_for_delivery()
    {

        $this->log_status_activity('Ready For Delivery');

        ///if there is an excess of units ordered compared to fulfilled return items
        $this->order_details->each(function ($order_detail) {
            $order_detail->units_accepted = $order_detail->units_fulfilled;

            $units_rejected = bcsub($order_detail->units, $order_detail->units_accepted,4);

            if ($units_rejected > 0) {
                if($order_detail->batch_location) {
                    $order_detail->batch_location->quantity = bcadd($order_detail->batch_location->quantity, $units_rejected,4);
                    $order_detail->batch->inventory = bcadd($order_detail->batch->inventory, $units_rejected,4);
                }
                $order_detail->units = bcadd($order_detail->units, $units_rejected * -1);

                ///update sale order ledger
                $this->journal->debit(($units_rejected * $order_detail->unit_sale_price) * 100);
            }

            $order_detail->push();
        });

        $this->journal->resetCurrentBalances();

        $this->subtotal = $this->subtotal_calculated;
        $this->total = ($this->subtotal - $this->discount);
        $this->status = 'ready for delivery';
        $this->save();

        return $this;
    }

    public function delivered()
    {
        $this->log_status_activity('Delivered');

        $this->status = 'delivered';
        $this->delivered_at = Carbon::now();

        //update due date with terms
        $this->setDueDate();
        //dd($this);
        $this->save();

        return $this;
    }

    /**
     * @return $this
     */
    public function close()
    {
        $this->status = 'closed';
        $this->save();

        return $this;
    }

    public function log_status_activity($activity = null, $properties = null)
    {
        activity('sale-order-status')
            ->causedBy(Auth::user())
            ->performedOn($this)
            ->withProperties($properties)
            ->log($activity);
    }

    public function setDueDate()
    {
        if ($this->expected_delivery_date || $this->delivered_at) {
            $this->due_date = ($this->expected_delivery_date ? $this->expected_delivery_date->addDays($this->terms) : $this->delivered_at->addDays($this->terms));
        }
    }

    public function getOrderDetail($batch_id, $unit_cost, $sale_price, $sold_as_name)
    {
        return $this->order_details()
            ->where('batch_id', $batch_id)
            ->where('sold_as_name', $sold_as_name)
            ->whereNull('units_accepted')
            ->where('unit_sale_price', $sale_price*100)
            ->where('unit_cost', $unit_cost*100)
            ->with('batch_location')
            ->first();
    }

    public function batches()
    {
        return $this->hasManyThrough(Batch::class, OrderDetail::class, 'sale_order_id', 'id', 'id', 'batch_id');
    }

    public function hasExciseTax()
    {
        if ($this->destination_license) {
            return  ! empty($this->destination_license) && in_array($this->destination_license->license_type_id, [4, 5, 11]);
        } else {
            return (bool) (stripos($this->customer_type, 'retailer') !== false);
        }
    }

    public function getRequiresManagerApprovalAttribute()
    {
        foreach ($this->order_details as $order_detail) {
            if(!$order_detail->batch_location) continue;
            if (! $order_detail->batch_location->price_approved) {
                return true;
            }
        }
        if (! $this->discount_approved) {
            return true;
        }

        return false;
    }

    public function hasOrderDetailWithNoPrice()
    {
        $needs_price = false;
        $this->order_details->each(function ($order_detail) use (&$needs_price) {
            if (is_null($order_detail->unit_sale_price) || $order_detail->unit_sale_price === 0) {
                $needs_price = true;
            }
        });

        return $needs_price;
    }

    public function hasDiscount()
    {
        return (bool) ($this->discount > 0);
    }

    public function isHold()
    {
        return (bool) ($this->status == 'hold');
    }

    public function isReadyToDeliver()
    {
        ///all order_details need to be fulfilled
        $all_items_fullfilled = true;
        $this->order_details_cog->each(function ($order_detail) use (&$all_items_fullfilled) {
            if (is_null($order_detail->units_fulfilled)) {
                $all_items_fullfilled = false;
                return false;
            }
        });

        return (bool) ($this->status == 'ready to pack' && $all_items_fullfilled);
    }

    public function isReadyToBePack()
    {
        return $this->isHold() && ! $this->requires_manager_approval;
    }

    public function isReadyToPack()
    {
        return (bool) ($this->status == 'ready to pack');
    }

    public function isPacked()
    {
        return (bool) ($this->status == 'ready for delivery');
    }

    public function isOpen()
    {
        return $this->isHold();

        return (bool) ($this->status == 'open');
    }

    public function isReadyForDelivery()
    {
        return (bool) ($this->status == 'ready for delivery');
    }

    public function isInTransit()
    {
        return (bool) ($this->status == 'in-transit');
    }

    public function isDelivered()
    {
        return (bool) ($this->status == 'delivered');
    }

    public function canVoid()
    {
        return ! $this->order_details->count();
    }

    public function canDeliverOrder()
    {
        return Auth::user()->can('so.deliver_order') && $this->isReadyForDelivery() && $this->balance <= 0 && ! $this->requires_manager_approval;
    }

    public function canAddItems()
    {
        return (Auth::user()->can('batches.sell') && !$this->isReadyForDelivery() && !$this->isDelivered()) && !$this->trashed() && !$this->location->trashed();
    }

    public function canReverse()
    {
        if(Auth::user()->hasRole('sauce') && $this->isReadyToPack()) {
            return false;
        }
        return (!$this->isHold() && !$this->isDelivered()) || ($this->isDelivered() && Auth::user()->hasPermission('so.reverse_delivered'));
    }

    public function recalculatePctDiscount()
    {
        $current_discount = $this->total;

//        dump($this->balance);
//        dd($this);

        $this->discount = $this->subtotal * ($this->discount_applied / 100);

        $discount_change = bcsub($current_discount, $this->discount, 2);

        $this->journal->credit($discount_change * 100);
        $this->journal->resetCurrentBalances();

        $this->calculateTotals();
    }

    public function removeDiscount()
    {
        if(!$this->discount) return;
        $this->journal->credit($this->discount * 100);
        $this->journal->resetCurrentBalances();

        $this->discount_approved = 1;
        $this->discount = 0;
        $this->discount_applied = 0;
        $this->discount_type = 'amt';
        $this->save();

        $this->refresh();
        $this->calculateTotals();

        activity('sale-order')
            ->causedBy(Auth::user())
            ->performedOn($this)
            ->log('Discount Removed');
    }

    public function approveDiscount()
    {
        if (! $this->discount_approved) {
            $this->discount_approved = 1;
            $this->save();

            $activity_prop = collect([
                'Discount Approved' => display_currency($this->discount),
            ]);

            activity('sale-order')
                ->causedBy(Auth::user())
                ->performedOn($this)
                ->withProperties($activity_prop)
                ->log('Discount Approved');
        }

        //approve any line item discounts
        foreach ($this->order_details as $order_detail) {
            if (! $order_detail->batch_location->price_approved) {
                $order_detail->batch_location->price_approved = 1;
                $order_detail->batch_location->save();

                $activity_prop = collect([
                    'Batch ID' => $order_detail->batch->id,
                    'SKU' => $order_detail->batch->ref_number,
                    'Name' => $order_detail->sold_as_name,
                    'Qty' => $order_detail->units,
                    'Suggested Sale Price' => display_currency($order_detail->batch->suggested_unit_sale_price),
                    'Price Approved' => display_currency($order_detail->unit_sale_price),
                ]);

                activity('sale-order')
                    ->causedBy(Auth::user())
                    ->performedOn($order_detail->sale_order)
                    ->withProperties($activity_prop)
                    ->log('Line Discount Approved');
            }
        }

        return;
    }
    public function rejectDiscount()
    {

        foreach ($this->order_details as $order_detail) {
            if (! $order_detail->batch_location->price_approved) {
                $batchLocation = $order_detail->batch_location;

                $batchLocation->price_approved = 1;
                $batchLocation->order_detail->unit_sale_price = $batchLocation->order_detail->batch->suggested_unit_sale_price;

                $activity_prop = collect([
                    'Batch ID' => $order_detail->batch->id,
                    'SKU' => $order_detail->batch->ref_number,
                    'Name' => $order_detail->sold_as_name,
                    'Qty' => $order_detail->units,
                    'Suggested Sale Price' => display_currency($order_detail->batch->suggested_unit_sale_price),
                    'Price Rejected' => display_currency($order_detail->getOriginal('unit_sale_price')),
                ]);

                $batchLocation->push();

                activity('sale-order')
                    ->causedBy(Auth::user())
                    ->performedOn($this)
                    ->withProperties($activity_prop)
                    ->log('Line Discount Rejected');
            }

        }

        $this->refresh();

        if ($this->discount) {

            $this->discount = 0;
            $this->discount_applied = 0;
            $this->discount_type = 'none';
            $this->discount_description = null;
            $this->discount_approved = 1;

            $activity_prop = collect([
                'Discount Rejected' => display_currency($this->getOriginal('discount')),
            ]);

            activity('sale-order')
                ->causedBy(Auth::user())
                ->performedOn($this)
                ->withProperties($activity_prop)
                ->log('Discount Rejected');
        }

        $this->subtotal = $this->subtotal_calculated;
        $this->total = ($this->subtotal - $this->discount);

        $total_change = (float)bcsub($this->total, $this->getOriginal('total'), 2);

        if($total_change < 0) {
            $this->journal->debit($total_change * -100);
        } elseif($total_change > 0) {
            $this->journal->credit($total_change * 100);
        }

        $this->journal->resetCurrentBalances();

        $this->save();
    }

    public function todaysOrders()
    {
        return static::select(DB::raw('COUNT(id) as order_count'), DB::raw('sum(subtotal) as subtotal'))
            ->whereDate('txn_date', Carbon::now()->toDateString())
            ->whereIn('status', ['delivered', 'returned'])
            ->first();
    }

    public function weeksOrders()
    {
        return static::select(DB::raw('COUNT(id) as order_count'), DB::raw('sum(subtotal) as subtotal'))
            ->whereBetween('txn_date', [Carbon::now()->startOfWeek()->format('Y-m-d'), Carbon::now()->endOfWeek()->format('Y-m-d')])
            ->whereYear('txn_date', Carbon::now()->year)
            ->whereIn('status', ['delivered', 'returned'])
            ->first();
    }

    public function monthsOrders()
    {
        return static::select(DB::raw('COUNT(id) as order_count'), DB::raw('sum(subtotal) as subtotal'))
            ->whereMonth('txn_date', Carbon::now()->month)
            ->whereYear('txn_date', Carbon::now()->year)
            ->whereIn('status', ['delivered', 'returned'])
            ->first();
    }

    public function quartersOrders()
    {
        return static::select(DB::raw('COUNT(id) as order_count'), DB::raw('sum(subtotal) as subtotal'))
            ->where(DB::raw('QUARTER(txn_date)'), DB::raw('QUARTER(NOW())'))
            ->whereYear('txn_date', Carbon::now()->year)
            ->whereIn('status', ['delivered', 'returned'])
            ->first();
    }

    public function exciseTax()
    {
        return static::select(DB::raw('COUNT(id) as order_count'), DB::raw('sum(tax) as excise_tax'), DB::raw('QUARTER(txn_date) as Quarter'), DB::raw('YEAR(txn_date) as Year'))
//            ->where(DB::raw("QUARTER(txn_date)"), '>=', DB::raw("QUARTER(date_sub(NOW(), INTERVAL 1 QUARTER))"))
//            ->whereYear('txn_date', Carbon::now()->year)
            ->whereDate('txn_date', '>=', Carbon::now()->subQuarter(1)->firstOfQuarter())
            ->whereIn('status', ['delivered', 'returned'])
            ->groupBy('Quarter')
            ->groupBy('Year')
            ->get();
    }

    public function set_order_id()
    {
        $this->ref_number = $this->new_ref_number(($this->sale_type == 'transfer' ? 'TR' : 'SO'));
        $this->save();

        return $this;
    }

    public function sales_by_location($dates)
    {
        return static::select(
            'locations.name',
            DB::raw('sum(orders.total) as total'),
            DB::raw('count(orders.id) as count')
        )
                ->join('locations', 'orders.location_id', '=', 'locations.id')
                ->deliveredOrders()
                ->withDateRange($dates)
                ->groupBy('locations.name')
                ->get();
    }

    public function sales_by_rep($dates)
    {
        return static::select(
            'users.name',
            DB::raw('sum(orders.total) as total'),
            DB::raw('count(orders.id) as count')
        )
            ->join('users', 'orders.sales_rep_id', '=', 'users.id')
            ->deliveredOrders()
            ->withDateRange($dates)
            ->groupBy('users.name')
            ->get();
    }

    public function sales_by_location_sales_rep($dates)
    {
        return static::select([
            'locations.name as location',
            'sales_reps.name as sales_rep',
            'categories.name as category',
            DB::raw('sum(order_details.units_accepted) as unit_count'),
            DB::raw('sum(order_details.units_accepted * order_details.unit_sale_price)/100 as category_revenue'),
        ])
            ->deliveredOrders()
            ->withDateRange($dates)
            ->joinBatchAndCategories()
            ->join('locations', 'orders.location_id', '=', 'locations.id')
            ->join('users as sales_reps', 'orders.sales_rep_id', '=', 'sales_reps.id')
            ->groupBy('locations.name')
            ->groupBy('sales_reps.name')
            ->groupBy('categories.name')
            ->orderBy('locations.name')
            ->orderBy('sales_reps.name')
            ->get();

    }

    public function sales_rep_by_day($dates)
    {
        return static::select(
            'users.name as location_name',
            DB::raw('date_format(orders.delivered_at, "%a %m/%d") as day_year'),
            DB::raw('round(sum(orders.total), 0) as total')
        )
            ->join('users', 'orders.sales_rep_id', '=', 'users.id')
            ->deliveredOrders()
            ->withDateRange($dates)
            ->groupBy(
                'users.name',
                DB::raw('YEAR(delivered_at)'),
                DB::raw('DAYOFYEAR(delivered_at)'),
                DB::raw('date_format(delivered_at, "%a %m/%d")')
            )
            ->orderBy(DB::raw('YEAR(delivered_at)'))
            ->orderBy(DB::raw('DAYOFYEAR(delivered_at)'))
            ->get();
    }

    public function sales_by_day($dates)
    {
        return static::select(
            'locations.name as location_name',
            DB::raw('date_format(orders.delivered_at, "%a %m/%d") as day_year'),
            DB::raw('round(sum(orders.total), 0) as total')
        )
            ->join('locations', 'orders.location_id', '=', 'locations.id')
            ->deliveredOrders()
            ->withDateRange($dates)
            ->groupBy(
                'locations.name',
                DB::raw('YEAR(delivered_at)'),
                DB::raw('DAYOFYEAR(delivered_at)'),
                DB::raw('date_format(delivered_at, "%a %m/%d")')
            )
            ->orderBy(DB::raw('YEAR(delivered_at)'))
            ->orderBy(DB::raw('DAYOFYEAR(delivered_at)'))
            ->get();
    }

    public function sales_by_week($dates)
    {
        return static::select(
            'locations.name as location_name',
            DB::raw('date_format(orders.delivered_at, "Wk %V, \'%y") as week_year'),
            DB::raw('round(sum(orders.total), 0) as total')
        )
            ->join('locations', 'orders.location_id', '=', 'locations.id')
            ->deliveredOrders()
            ->withDateRange($dates)
            ->groupBy(
                'locations.name',
                DB::raw('YEAR(delivered_at)'),
                DB::raw('WEEK(delivered_at)'),
                DB::raw('date_format(delivered_at, "Wk %V, \'%y")')
            )
            ->orderBy(DB::raw('YEAR(delivered_at)'))
            ->orderBy(DB::raw('WEEK(delivered_at)'))
            ->orderBy(DB::raw('location_name'))
            ->get();
    }

    public function sales_by_month($dates)
    {
        return static::select(
            'locations.name as location_name',
            DB::raw('date_format(orders.delivered_at, "%b, %y") as month_year'),
            DB::raw('round(sum(orders.total), 0) as total')
        )
            ->join('locations', 'orders.location_id', '=', 'locations.id')
            ->deliveredOrders()
            ->withDateRange($dates)
            ->groupBy(
                'locations.name',
                DB::raw('YEAR(delivered_at)'),
                DB::raw('MONTH(delivered_at)'),
                DB::raw('date_format(delivered_at, "%b, %y")')
            )
            ->orderBy(DB::raw('YEAR(delivered_at)'))
            ->orderBy(DB::raw('MONTH(delivered_at)'))
            ->orderBy(DB::raw('location_name'))
            ->get();
    }

    public function sales_by_quarter($dates)
    {
        return static::select(
            'locations.name as location_name',
            DB::raw('CONCAT("Q",QUARTER(orders.delivered_at),"-",YEAR(orders.delivered_at)) as quarter_year'),
            DB::raw('round(sum(orders.total), 0) as total')
        )
            ->join('locations', 'orders.location_id', '=', 'locations.id')
            ->deliveredOrders()
            ->withDateRange($dates)
            ->groupBy(
                'locations.name',
                DB::raw('YEAR(orders.delivered_at)'),
                DB::raw('QUARTER(orders.delivered_at)'),
                DB::raw('CONCAT("Q",QUARTER(orders.delivered_at),"-",YEAR(orders.delivered_at))')
            )
            ->orderBy(DB::raw('YEAR(orders.delivered_at)'))
            ->orderBy(DB::raw('QUARTER(orders.delivered_at)'))
            ->orderBy(DB::raw('location_name'))
            ->get();
    }

    public function topProductsByCategory($dates)
    {
        return static::select(
            'categories.id',
            'categories.name',
            'batches.id as batch_id',
            'batches.name as batch_og_name',
            'order_details.sold_as_name',
            'vendors.name as vendor_name',
            DB::raw('sum(units_accepted) as count'),
            DB::raw('avg(unit_sale_price)/100 as avg_price'),
            DB::raw('sum(units_accepted * unit_sale_price)/100 as sales')
            )
            ->deliveredOrders()
            ->withDateRange($dates)
            ->joinBatchAndCategories()
            ->leftjoin('orders as po', 'batches.purchase_order_id', '=', 'po.id')
            ->leftjoin('users as vendors', 'po.vendor_id', '=', 'vendors.id')
            ->groupBy(
                'categories.id',
                'categories.name',
                'batches.id',
                'batches.name',
                'order_details.sold_as_name',
                'vendors.name'
            )
            ->orderBy('count', 'desc')
            ->orderBy('categories.name');
    }

    public function categoryRevenueByDate($date)
    {
        return static::select(
            'categories.id',
            'categories.name',
            DB::raw('(sum(units_accepted * unit_sale_price) / 100) as revenue')
            )
            ->deliveredOrders()
            ->withDateRange($date)
            ->joinBatchAndCategories()
            ->having(DB::raw('(sum(units_accepted * unit_sale_price) / 100)'), '>', 0)
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('revenue', 'desc');
    }

    public function pendingOrdersForVendorBatches($vendor_id)
    {
        $query = static::select('orders.status', DB::raw('(order_details.unit_cost * sum(order_details.units))/100 as cost_sold'))
            ->join('order_details', 'orders.id', '=', 'order_details.sale_order_id')
            ->join('batches', 'order_details.batch_id', '=', 'batches.id')
            ->join('orders as po', 'batches.purchase_order_id', '=', 'po.id')
            ->join('users as vendors', 'po.vendor_id', '=', 'vendors.id')
            ->where('vendors.id', $vendor_id)
            ->where('orders.status', '!=', 'delivered')
            ->groupBy('orders.status')
            ->groupBy('order_details.unit_cost');
        //inner join users as vendors on po.vendor_id = vendors.id

        return $query;
    }

    public static function app_search($q)
    {
        parent::$search_table = 'customer';

        return parent::app_search($q);
    }
}
