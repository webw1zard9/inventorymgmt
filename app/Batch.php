<?php

namespace App;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use App\Events\BatchAllocated;
use App\Events\BatchCreated;
use App\Events\BatchDeleted;
use App\Presenters\PresentableTrait;
use App\Scopes\PurchaseOrderLocationScope;
use App\Traits\ActivityLogTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Scottlaurent\Accounting\Services\Accounting;

class Batch extends Model
{
    use PresentableTrait, ActivityLogTrait, EagerLoadPivotTrait;

    protected static $logAttributes = ['*'];

    protected $guarded = [];

    protected $casts = [
        'cultivation_date' => 'datetime',
        'packaged_date' => 'datetime',
        'allocation_created_at' => 'datetime',
        'tested_at' => 'datetime',
        'character' => 'array',
    ];

    protected $custom_columns = [
        'batches.id',
//        'batches.parent_id',
//        'batches.child_id',
        'batches.purchase_order_id',
        'batches.category_id',
        'batches.brand_id',
        'batches.fund_id',
        'batches.status',
        'batches.description',
        'batches.sales_notes',
        'batches.type',
        'batches.ref_number',
        'batches.units_purchased',
        'batches.uom',
        'batches.unit_price',
        'batches.avg_unit_price',
//        'batches.suggested_unit_sale_price',
//        'batches.min_flex',
        'batches.created_at',
        'batches.updated_at',
        'brands.name as brand_name',
        'categories.name as cat_name',
    ];

    protected $appends = ['cost'];

    public $total_converted_cost = 0;

    public $total_converted_grams = 0;

    public $shortage_cost = 0;

    public $shortage_grams = 0;

    protected $available_weight_grams = 0;

    protected $presenter = \App\Presenters\Batch::class;

    protected $dispatchesEvents = [
        'created' => BatchCreated::class,
        'deleted' => BatchDeleted::class,
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function parent_batch()
    {
        return $this->belongsTo(Batch::class, 'parent_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function cultivator()
    {
        return $this->belongsTo(User::class, 'cultivator_id');
    }

    public function testing_laboratory()
    {
        return $this->belongsTo(User::class, 'testing_laboratory_id');
    }

    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    public function license()
    {
        return $this->belongsTo(License::class);
    }

    public function tax_rate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function child_batches()
    {
        return $this->hasMany(Batch::class, 'parent_id');
    }

    public function children_batches()
    {
        return $this->hasMany(Batch::class, 'parent_id')->with('children_batches.order_details_cog.sale_order.customer');
    }

    public function source_batches()
    {
        return $this->hasMany(Batch::class, 'child_id');
    }

    public function created_batch()
    {
        return $this->belongsTo(Batch::class, 'child_id');
    }

    public function transfer_log()
    {
        return $this->hasOne(TransferLog::class);
    }

    public function transfer_logs()
    {
        return $this->hasMany(TransferLog::class);
    }

    public function transfer_logs_prepack()
    {
        return $this->transfer_logs()->where('type', 'Pre-Pack');
    }

    public function transfer_logs_reconcile()
    {
        $builder = $this->transfer_logs()->where('type', 'Reconcile');

//        if (Auth::user()->hasLocation()) {
//            $builder->where('location_id', Auth::user()->current_location->id);
//        }

        return $builder;
    }

    public function transfer_pre_pack()
    {
        return $this->transfer_logs_prepack();
    }

    public function transfer_log_detail()
    {
        return $this->hasOne(TransferLogDetail::class);
    }

    public function transfer_log_details()
    {
        return $this->hasMany(TransferLogDetail::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function purchase_order()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchase_order_without_location_scope()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id')->withoutGlobalScope(PurchaseOrderLocationScope::class);
    }

    public function pickups()
    {
        return $this->hasMany(BatchPickup::class);
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class)
            ->as('batch_location')
            ->withPivot(
                'id',
                'parent_id',
                'order_detail_id',
                'quantity',
                'name',
                'unit_price',
                'suggested_unit_sale_price',
                'min_flex',
                'approved',
                'price_approved',
                'cost_change',
                'approved_at',
                'transfer_log_id',
                'return_item'
            )
            ->whereIn('location_id', Auth::user()->only_my_locations->pluck('id'))
            ->using(BatchLocation::class)
            ->withTrashed()
            ->withTimestamps();
    }

    public function locations_aggregate()
    {
        return $this->belongsToMany(Location::class, 'batch_location_aggregate')
            ->as('batch_location_aggregate')
            ->withPivot(
                'id',
                'onhand_inventory',
                'available_inventory',
                'pending_inventory',
                'approved_inventory',
                'waiting_approval_inventory',
                'suggested_unit_sale_price',
                'min_flex',
                'location_unit_price',
                'location_batch_name',
            )
            ->whereIn('location_id', Auth::user()->only_my_locations->pluck('id'))
            ->using(BatchLocationAggregate::class)
            ->orderBy('name')
            ->withTrashed()
            ->withTimestamps();
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function order_details_cog()
    {
        return $this->hasMany(OrderDetail::class)->where('cog', 1);
    }

    public function order_details_sold()
    {
        return $this->hasMany(OrderDetail::class)->whereNotNull('sale_order_id');
    }

    public function order_details_not_accepted()
    {
        return $this->order_details()->whereNull('units_accepted');
    }

    public function order_details_accepted()
    {
        return $this->order_details()->where('units_accepted', '>', 0);
    }

    public function myPickups()
    {
        return $this->pickups()->where('user_id', Auth::user()->id);
    }

    public function hasPickups()
    {
        return (bool) $this->pickups()->count();
    }

    public function getAvailableForSaleAttribute($value)
    {
        return floatval(bcadd($this->approved_inventory_by_location->sum('batch_location.quantity'), $this->sold_inventory_by_location->sum('batch_location.quantity'), 4));
    }

    public function getAvailableForAllocationAttribute()
    {
//        return floatval(bcsub($this->inventory, $this->locations()->where('approved',1)->sum('batch_location.quantity'), 4));
        return floatval(bcsub($this->inventory, $this->locations->sum('batch_location.quantity'), 4));
    }

//    public function getPendingAllocationAttribute()
//    {
//        return floatval($this->locations()->where('approved',0)->sum('batch_location.quantity'));
//    }

    public function scopeMyLocation($query)
    {
        if (Auth::check() && Auth::user()->hasLocation()) {
            return $query->where('location_id', '=', Auth::user()->current_location->id);
        }
    }

    public static function find($id)
    {
//        dd(Auth::user());

        $builder = Batch::where('batches.id', $id)
            ->with('locations_aggregate')
            ->with('locations');

        return $builder->first();
    }

    public function allocated_inventory()
    {
        $builder = $this->locations()->whereNull('order_detail_id');

//        dd(Auth::user()->only_my_locations->pluck('id'));
        if (Auth::check() && Auth::user()->only_my_locations->count()) {
            $builder->whereIn('location_id', Auth::user()->only_my_locations->pluck('id'));
        }

        return $builder;
    }

    public function allocated_and_sold_inventory()
    {
        $builder = $this->locations();

        if (Auth::check() && Auth::user()->only_my_locations->count()) {
            $builder->whereIn('location_id', Auth::user()->only_my_locations->pluck('id'));
        }

        return $builder;
    }

    public function reconciled_inventory()
    {
        $builder = $this->locations()->whereNotNull('transfer_log_id');

        if (Auth::check() && Auth::user()->only_my_locations->count()) {
            $builder->whereIn('location_id', Auth::user()->only_my_locations->pluck('id'));
        }

        return $builder;

    }

    public function approved_inventory_by_location()
    {
//        if(!Auth::user()->current_location->exists) return 0;
        $builder = $this->locations()
            ->where('approved', '=', 1)
            ->whereNull('order_detail_id');

//        if (Auth::check() && Auth::user()->only_my_locations->count()) {
//            $builder->whereIn('location_id', Auth::user()->only_my_locations->pluck('id'));
//        }

        return $builder;
    }

    public function sold_inventory_by_location()
    {
        return $this->locations()
//            ->whereIn('location_id', Auth::user()->only_my_locations->pluck('id'))
            ->whereNotNull('order_detail_id');
    }

    public function getOrderDetailsCogSumMarginAttribute()
    {
        return $this->order_details_cog->sum('margin');
    }

    public function getCostAttribute()
    {
        return $this->units_purchased * $this->unit_price;
    }

    public function getPreTaxCostAttribute()
    {
        return $this->unit_price;
    }

    public function getTopLevelParentAttribute()
    {
        try {
            $batch = $this;
            do {
                if (!is_null($batch->parent_batch)) {
                    $batch = $batch->parent_batch;
                }
            } while (!empty($batch->parent_id));
        } catch (\Exception $e) {
            dd($e);
        }

        return $batch;
    }

    public function vendor()
    {
        if ($this->topLevelParent) {
            return $this->topLevelParent->purchase_order->vendor;
        } else {
            return null;
        }
    }

//    public function myPickupInTransit()
//    {
//        return $this->hasOne(BatchPickup::class)
//            ->where('user_id', Auth::user()->id);
//    }

//    public function scopeWithInventory($query)
//    {
//        return $query->where('inventory', '>', 0);
//    }

    public function scopeFilters($query, $filters)
    {
        return $filters->apply($query);
    }

    public function getAddedToInventoryAttribute()
    {
        $date = $this->created_at;
        if ($this->purchase_order) {
            $date = $this->purchase_order->txn_date;
        }

        if ($date->isToday()) {
            return 'Today';
        } else {
            return $date->diffForHumans();
        }
    }

    public function getAddedToInventoryDateAttribute()
    {
        $date = $this->created_at;
        if ($this->purchase_order) {
            $date = $this->purchase_order->txn_date;
        }

        return $date->format('m/d/Y');
    }

    public function getAllocatedAgoAttribute()
    {
        if (! $this->allocated_inventory->count()) {
            return '';
        }

        return $this->allocated_inventory->first()->batch_location->created_at->diffForHumans();
    }

    public function getStatusAttribute($value)
    {
        return $value;
    }

//    public function getHarvestDateAttribute()
//    {
//        if (! $this->top_level_parent->cultivation_date) {
//            return '--';
//        }
//
//        return $this->top_level_parent->cultivation_date->format('m/d/Y');
//    }

    public function getRevenueAttribute()
    {
        $revenue = $this->order_details->sum('revenue');

        if ($this->children_batches->count()) {
            $this->loopBatches($this->children_batches, 'revenue', $revenue);
        }

        return $revenue;
    }

    public function getAvailableWeightGramsAttribute()
    {
        $available_wt = ($this->inventory);

        if ($this->children_batches->count()) {
            $this->loopBatches($this->children_batches, 'inventory', $available_wt);
        }

        return $available_wt;
    }

    public function getAvailableWeightPoundsAttribute()
    {
        return $this->getAvailableWeightGramsAttribute() / config('inventorymgmt.uom.lb');
    }

    protected function loopBatches($child_batches, $key, &$val)
    {
        foreach ($child_batches as $child_batch) {
            $val += $child_batch->{$key};

            if ($child_batch->children_batches->count()) {
                $this->loopBatches($child_batch->children_batches, $key, $val);
            }
        }
    }

    public function getPackagedWeightGramsAttribute()
    {
        return $this->transfer_logs->sum('start_wt_grams');
    }

    public function getPackagedWeightPoundsAttribute()
    {
        return number_format($this->transfer_logs->sum('start_wt_grams') / config('inventorymgmt.uom.lb'), 4);
    }

    public function getWeightAcceptedGramsAttribute()
    {
        $weight_grams = $this->order_details->sum('weight_accepted_grams');

//        if($this->children_batches->count()) {
//            $this->loopBatchesOrderDetailsSum($this->children_batches, 'weight_grams_accepted', $weight_grams);
//        }
        return $weight_grams;
    }

    public function getWeightAcceptedPoundsAttribute()
    {
        return number_format($this->getWeightAcceptedGramsAttribute() / config('inventorymgmt.uom.lb'), 4);
    }

    public function getWeightPendingGramsAttribute()
    {
        $weight_grams = $this->order_details->sum('weight_pending_grams');

//        if($this->children_batches->count()) {
//            $this->loopBatchesOrderDetailsSum($this->children_batches, 'weight_grams_pending', $weight_grams);
//        }
        return $weight_grams;
    }

    public function getWeightPendingPoundsAttribute()
    {
        return number_format($this->getWeightPendingGramsAttribute() / config('inventorymgmt.uom.lb'), 4);
    }

    protected function loopBatchesOrderDetailsSum($child_batches, $key, &$val)
    {
        foreach ($child_batches as $child_batch) {
            $val += $child_batch->order_details->sum($key);

            if ($child_batch->children_batches->count()) {
                $this->loopBatchesOrderDetailsSum($child_batch->children_batches, $key, $val);
            }
        }
    }

    public function getUnitsPurchasedGramsAttribute()
    {
        return number_format($this->units_purchased, 4);
    }

    public function getRequiresRetagAttribute()
    {
    }

    public function inTesting()
    {
        return $this->testing_status == 'In-Testing';
    }

    public function passedTesting()
    {
        return $this->testing_status == 'Passed';
    }

    public function canAllocate()
    {
        return $this->track_inventory && $this->total_available_inventory && Auth::user()->hasMultiLocations();
    }

    public function canChangePOQuantityPrice()
    {
//        return ! ($this->order_details->isNotEmpty() || ($this->units_purchased != $this->inventory) || $this->locations->count());
        return ($this->track_inventory && $this->allocated_and_sold_inventory->count() == 1);
    }

    public function canSell()
    {
        return $this->suggested_unit_sale_price &&
                Auth::user()->hasLocation() &&
                $this->available_for_sale &&
                Gate::allows('batches.sell');
    }

    public function canCreatePackages()
    {
        return $this->canTransfer() && $this->testing_status != 'In-Testing';
    }

    public function canTransfer()
    {
        return $this->inventory > 0;

        return $this->top_level_parent->testing_status == 'Passed';

        return in_array($this->category_id, [1, 20]) && $this->inventory;
    }

    public function pickup($quantity)
    {
        $this->inventory = bcsub($this->inventory, $quantity, 2);
        $this->transit = bcadd($this->transit, $quantity, 2);

        $this->addPickup($quantity);
        $this->save();

        return $this;
    }

    public function revertMyPickup($quantity)
    {
        $this->status = 'inventory';
        $this->transit = bcadd($this->transit, $quantity, 2);
        $this->sold = bcsub($this->sold, $quantity, 2);

        $this->addPickup($quantity);
        $this->save();

        return $this;
    }

    public function addPickup($quantity)
    {
        if ($batch_pickup = $this->myPickupInTransit) {
            $batch_pickup->units = bcadd($batch_pickup->units, $quantity, 2);
            $batch_pickup->save();
        } else {
            $batch_pickup = new BatchPickup();
            $batch_pickup->user_id = Auth::user()->id;
            $batch_pickup->units = $quantity;
            $this->pickups()->save($batch_pickup);
        }

        return $this;
    }

    public function sell($quantity)
    {
//        $this->transit = bcsub($this->transit, $quantity, 2);
//        $this->sold = bcadd($this->sold, $quantity, 2);

        if ($this->inventory == 0) {
            $this->status = 'sold';
        }

        $this->save();

        return $this;
    }

    public function release($quantity)
    {
        $this->inventory = bcadd($this->inventory, $quantity, 4);
        $this->transit = bcsub($this->transit, $quantity, 4);
        $this->save();

        return $this;
    }

    public function calculateCultTax()
    {
        $this->tax = 0;
        $this->unit_tax_amount = 0;

        if (! empty($this->tax_rate)) {
            $tax_rate_quantity = $this->inventory;
            $this->unit_tax_amount = $this->tax_rate->amount;

            if ($this->tax_rate->uom != $this->uom) {
                $conv_rate = Conversion::getRate($this->uom, $this->tax_rate->uom);
                if (! $conv_rate) {
                    throw new \Exception('No conversion rate not found. From:'.$this->uom.' - To:'.$this->tax_rate->uom);
                }
                $tax_rate_quantity = ($this->inventory * $conv_rate->value);
                $this->unit_tax_amount = $this->tax_rate->amount * $conv_rate->value;
            }

            $this->tax = ($tax_rate_quantity * $this->tax_rate->amount);
        } else {
            return 0;
        }
    }

    public function transfer($start_weight, $qty_to_xfer, $packages_created, $packer_name = 'System', $new_batch_name = null)
    {
        // --> $used_weight  -> $start_weight
        // --> $qty_to_xfer  -> $qty_to_xfer

        $transfer_log_data = [
            'user_id' => Auth::user()->id,
            'batch_id' => $this->id,
            'quantity_transferred' => $qty_to_xfer,
            'start_wt_grams' => $start_weight,
            'packer_name' => $packer_name,
        ];

//        dump($start_weight);

        $xfer_log = new TransferLog($transfer_log_data);

        if ($this->wt_based) {
            $g_price = $this->subtotal_price / 1;
        } else {
            $g_price = $this->unit_price / 1;
        }

        if (empty($start_weight)) {
            $start_weight = $qty_to_xfer * 1;
        }

//        dump("start wt");
//        dump($start_weight);
//        dump('end');
//
//        dump('qty to xfer');
//        dump($qty_to_xfer);
//
//        dump($g_price);
//        dd('d');

        $total_cost = ($start_weight);
//        dump('total cost');
//        dump($total_cost);
        $total_grams = $start_weight;

        if (! $this->wt_grams) {
            $this->shortage_cost = ($qty_to_xfer * $this->unit_price) - $total_cost;
            $this->shortage_grams = ($qty_to_xfer) - $start_weight;
        }

        DB::beginTransaction();

        $new_batches_created = [];
        //dd($packages_created);
        foreach ($packages_created as $idx => $row) {
            if (is_null($row['category_id']) ||
                is_null($row['amount']) ||
                is_null($row['uom'])) {
                continue;
            }

            $grams = get_grams($row['uom']);

            $unit_price = round($g_price * $grams, 2);
            $batch_price = round($unit_price * $row['amount'], 2);

//            dump($row);
//            dump($row['uom']);
//            dump($unit_price);
//            dump($grams);
//            dump($batch_price);

            $this->total_converted_cost += $batch_price;
            $this->total_converted_grams += ($grams * $row['amount']);

            $pkg_name = ($new_batch_name ?: $this->name);

//            dump($total_converted_cost);

            if (! empty($row['ref_number']) && (! empty($row['increment_uid']) && $row['increment_uid'] == 'on')) { ///creating metrc packages
                //get start number
                $uid_split = str_split($row['ref_number'], 15);

                $xfer_log->save();

                if ($row['amount'] < 1) {
                    throw new \Exception('Cannot incremeber UID when amount is less than 1.');
                }

                for ($i = 1; $i <= $row['amount']; $i++) {
                    $uid = $uid_split[0].str_pad((int) $uid_split[1]++, 9, 0, STR_PAD_LEFT);

                    $create = [
                        'category_id' => $row['category_id'],
                        'brand_id' => $row['brand_id'],
                        'fund_id' => $row['fund_id'],
                        'license_id' => $this->license_id,
                        'tax_rate_id' => $this->tax_rate_id,
                        'name' => $pkg_name,
                        'description' => $this->description,
                        'uom' => $row['uom'],
                        'wt_grams' => 1,
                        'wt_based' => 1,
                        'parent_id' => $this->id,
                        'status' => 'inventory',
                        'type' => $this->type,
                        'ref_number' => $uid,
                        'units_purchased' => 1,
                        'inventory' => 1,
                        'unit_price' => $unit_price,
                        'subtotal_price' => $unit_price,
                        'tax' => 0,
                        'suggested_unit_sale_price' => 0,
                        'min_flex' => 0,
                        'max_flex' => 0,
                        'testing_status' => $this->testing_status,
                        'packaged_date' => ($row['packed_date'] ? Carbon::parse($row['packed_date']) : null),
                    ];

                    $batch = self::create($create);

                    $new_batches_created[] = new TransferLogDetail([
                        'batch_id' => $batch->id,
                        'action' => ($batch->wasRecentlyCreated ? 'Created' : 'Updated'),
                        'units' => 1,
                    ]);
                }

                if (! count($new_batches_created)) {
                    throw new \Exception('No Batches Created');
                }
            } else {
                $pkg = (! empty($row['ref_number']) ? $row['ref_number'] : 'PK'.mt_rand(100000, 999999));

                $match = [
                    'category_id' => $row['category_id'],
                    'brand_id' => $row['brand_id'],
                    'fund_id' => $row['fund_id'],
                    'license_id' => $this->license_id,
                    'tax_rate_id' => $this->tax_rate_id,
                    'name' => $pkg_name,
                    'description' => $this->description,
                    'uom' => $row['uom'],
                    'unit_price' => (string) ($unit_price * 100),
                    'packaged_date' => ($row['packed_date'] ? Carbon::parse($row['packed_date']) : null),
                ];

                if (! empty($row['ref_number'])) {
                    $match['ref_number'] = $pkg;
                }

                $create = [
                    'category_id' => $row['category_id'],
                    'parent_id' => $this->id,
                    'fund_id' => $row['fund_id'],
                    'license_id' => $this->license_id,
                    'tax_rate_id' => $this->tax_rate_id,
                    'status' => 'Inventory',
                    'type' => $this->type,
                    'ref_number' => $pkg,
                    'inventory' => $row['amount'],
                    'name' => $pkg_name,
                    'description' => $this->description,
                    'uom' => $row['uom'],
                    'units_purchased' => $row['amount'],
                    'unit_price' => $unit_price,
                    'subtotal_price' => $batch_price,
                    'cultivation_date' => $this->cultivation_date,
                    'packaged_date' => ($row['packed_date'] ? Carbon::parse($row['packed_date']) : null),
                    //                        'tax' => $this->cult_tax_amount(),
                    'suggested_unit_sale_price' => $this->suggested_unit_sale_price,
                    'min_flex' => $this->min_flex,
                    'max_flex' => $this->max_flex,
                    'testing_status' => $this->testing_status,
                ];

                //dump($match);
                //dump($create);
                //dd('end');
//                    $batch = $this->firstOrNew($match, $create);
                $batch = new self($create);
                $batch->calculateCultTax();

                //dd($batch);

                $batch->save();

                $xfer_log->save();

                $xfer_log->transfer_log_details()->create([
                    'batch_id' => $batch->id,
                    'action' => ($batch->wasRecentlyCreated ? 'Created' : 'Updated'),
                    'units' => $row['amount'],
                ]);
            }
        }

        //dump($xfer_log);
//        dd($new_batches_created);

        $xfer_log->transfer_log_details()->saveMany($new_batches_created);

        //dd('exit');
//        dump($this->total_converted_grams);
//        dump($this->total_converted_cost);

//        $this->total_converted_cost = $total_converted_cost;

//        dump($total_cost);

//        $inventory_loss = $total_cost - $this->total_converted_cost;
        $inventory_loss = (float) bcsub($total_cost, $this->total_converted_cost, 4);
        $inventory_loss_grams = (float) bcsub($total_grams, $this->total_converted_grams, 4);

//        dump('inv loss - cost / grams');
//        dump($inventory_loss);
//        dump($inventory_loss_grams);

        $xfer_log->inventory_loss = round($inventory_loss, 4);
        $xfer_log->inventory_loss_grams = round($inventory_loss_grams, 4);

//        dump('shortage - cost / grams');
//        dump($this->shortage_cost);
//        dump($this->shortage_grams);
//        dd('d');

        $xfer_log->shortage = round($this->shortage_cost, 4);
        $xfer_log->shortage_grams = round($this->shortage_grams, 4);

        $xfer_log->save();

        DB::commit();

        return $batch;
    }


    public function scopeSearch($query, $search)
    {
        return $query->where(function ($qry) use ($search) {
            $qry->where('batches.name', 'like', "%$search%")
                    ->orWhere('batches.description', 'like', "%$search%")
                    ->orWhere('batches.ref_number', 'like', "%$search%")
                    ->orWhere('categories.name', 'like', "%$search%")
                    ->orWhere('batch_location.name', 'like', "%$search%")
                    ->orWhere('brands.name', 'like', "%$search%");
        });
    }

    public function scopeJoinMisc($query, $search)
    {
        return $query->leftjoin('categories', 'batches.category_id', '=', 'categories.id')
            ->leftjoin('brands', 'batches.brand_id', '=', 'brands.id')
            ->leftjoin('batch_location', 'batches.id', '=', 'batch_location.batch_id');
    }

    public function scopeAllInventory($query, $filters = null, $with = [])
    {
        $builder = $query->baseCurrentInventory($filters, $with);

        if(Auth::check() && Auth::user()->active_locations->count()) {
            $builder->whereIn('locations.id', Auth::user()->active_locations->pluck('id'));
        }

        return $builder;
    }


    public function scopeCurrentInventory($query, $filters = null, $with = [])
    {
        $builder = $query->baseCurrentInventory($filters, $with);

        $builder->where(function ($q) {
            $q->where('batches.track_inventory', 0);

            if(Auth::user()->hasRole('salesrep')) {
                $q->orWhere('batch_location_aggregate.available_inventory', '>', 0);
            } else {
                $q->orWhere('batch_location_aggregate.onhand_inventory', '>', 0);
            }
        });

        if(Auth::check() && Auth::user()->active_locations->count()) {
            $builder->whereIn('locations.id', Auth::user()->active_locations->pluck('id'));
        }

        return $builder;
    }

    public function scopeSoldInventory($query, $filters = null, $with = [])
    {
        $builder = $query->baseCurrentInventory($filters, $with);

        $builder->where(function ($q) {
            $q->where('batches.track_inventory', 0)
                ->orWhere('batch_location_aggregate.onhand_inventory', '=', 0);
        });

        if(Auth::check() && Auth::user()->only_my_locations->count()) {
            $builder->whereIn('locations.id', Auth::user()->only_my_locations->pluck('id'));
        }

        $builder->groupBy('batches.id');

        return $builder;

    }

    public function scopeBaseCurrentInventory($query, $filters = null, $with = [])
    {

        $builder = $query->select([
            'batches.id',
            'batches.purchase_order_id',
            'batches.category_id',
            'batches.brand_id',
            'batches.name as original_batch_name',
            'batches.ref_number',
            'batches.uom',
            'batches.type',
            'batches.track_inventory',
            'batches.units_purchased',
            'batches.unit_price',
            'batches.avg_unit_price',
            'batches.created_at',
            'batches.updated_at',
            'batch_location_aggregate.onhand_inventory',
            'batch_location_aggregate.available_inventory',
            'batch_location_aggregate.pending_inventory',
            'batch_location_aggregate.suggested_unit_sale_price',
            'batch_location_aggregate.min_flex',
            'batch_location_aggregate.location_unit_price as location_unit_price',
            'batch_location_aggregate.location_batch_name as name',
            'locations.id as location_id',
            'locations.name as location_name',
            'batch_location_aggregate.created_at as allocation_created_at'
        ])
            ->join('batch_location_aggregate', 'batches.id', '=', 'batch_location_aggregate.batch_id')
            ->join('locations', 'locations.id', '=', 'batch_location_aggregate.location_id');

        if ($filters) {
            $builder->filters($filters);
        }

        if (count($with)) {
            $builder->with($with);
        }

        return $builder;
    }

    public function scopeCurrentInventoryWithSaleOrderBatches($query, $location_id, $sale_order_id, $search)
    {
        $builder = $query->BatchesOnOrderOutOfStock($location_id, $sale_order_id, $search);

        return $builder;
    }

    public function scopeBatchesOnOrderOutOfStock($query, $location_id, $sale_order_id, $search)
    {

        $instock_batches_with_order_quantity = $query->select($this->custom_columns)
            ->addSelect([
                'batches.track_inventory',
                'batch_location_aggregate.onhand_inventory',
                'batch_location_aggregate.available_inventory',
                'batch_location_aggregate.pending_inventory',
                'batch_location_aggregate.suggested_unit_sale_price',
                'batch_location_aggregate.min_flex',
                'batch_location_aggregate.location_unit_price as location_unit_price',
                'batch_location_aggregate.location_batch_name',
                'locations.id as location_id',
                'locations.name as location_name',
                'order_details.units as order_detail_units'
            ])
            ->leftjoin('categories', 'batches.category_id', '=', 'categories.id')
            ->leftjoin('brands', 'batches.brand_id', '=', 'brands.id')
            ->join('batch_location_aggregate', 'batches.id', '=', 'batch_location_aggregate.batch_id')
            ->join('locations', 'locations.id', '=', 'batch_location_aggregate.location_id')
            ->leftJoinSub($this->orderDetailsForSaleOrderId($sale_order_id), 'order_details', function ($join) {
                $join->on('batches.id', '=', 'order_details.batch_id');
            })
            ->where(function($query) {
                $query->where('batch_location_aggregate.available_inventory', '>', 0)
                    ->orWhere('order_details.units', '>', 0)
                    ->orWhere('batches.track_inventory', 0);
            })
            ->where('batch_location_aggregate.location_id', $location_id)
            ->orderBy('batch_location_aggregate.location_batch_name');

        foreach((array)$search as $s) {
            $instock_batches_with_order_quantity->where(function($q) use ($s) {
                $q->search('batch_location_aggregate.location_batch_name', $s)
                    ->orSearch('categories.name', $s)
                    ->orSearch('brands.name', $s)
                    ->orSearch('batches.ref_number', $s);
            });
        }

        return $instock_batches_with_order_quantity;
    }

    public function pendingSaleOrdersQuery()
    {
        //pending batches inventory subquery
        return SaleOrder::select([
            'batches.id as batch_id',
            'batch_location.location_id',
            DB::raw('((COALESCE(SUM(batch_location.quantity), 0) * -1) - COALESCE(SUM(order_details.units_fulfilled), 0)) AS pending_inventory')
        ])
            ->join('order_details', 'orders.id', '=', 'order_details.sale_order_id')
            ->join('batch_location', 'order_details.id', '=', 'batch_location.order_detail_id')
            ->join('batches', 'batch_location.batch_id', '=', 'batches.id')
            ->whereIn('orders.status', ['hold','ready to pack'])
            ->groupBy('batches.id')
            ->groupBy('batch_location.location_id');
    }

    public function orderDetailsForSaleOrderId($sale_order_id)
    {
        $sale_order_order_details = OrderDetail::select([
            'batch_id',
            DB::raw('SUM(order_details.units) AS units')
        ])
            ->where('sale_order_id', $sale_order_id)
            ->groupBy('batch_id');

        return $sale_order_order_details;
    }

    public function allPendingSaleOrdersQuery()
    {
        //all pending batches inventory subquery
        return SaleOrder::select([
            'batches.id as batch_id',
            DB::raw('((COALESCE(SUM(batch_location.quantity), 0) * -1) - COALESCE(SUM(order_details.units_fulfilled), 0) ) AS all_pending_inventory')
        ])
            ->join('order_details', 'orders.id', '=', 'order_details.sale_order_id')
            ->join('batch_location', 'order_details.id', '=', 'batch_location.order_detail_id')
            ->join('batches', 'batch_location.batch_id', '=', 'batches.id')
            ->whereIn('orders.status', ['hold','ready to pack'])
            ->groupBy('batches.id');
    }

    public function getLocationInventoryValues($location_id=null)
    {
        if($this->relationLoaded('locations_aggregate') && $this->locations_aggregate->count() > 1 && is_null($location_id)) {
            throw new \Exception("Unable to load location inventory values");
        }

        if($location_id) {
            $location_data = $this->locations_aggregate->firstWhere('id', $location_id)->batch_location_aggregate;
        } else {
            $location_data = $this->locations_aggregate->first()->batch_location_aggregate;
        }

        return $location_data;
    }


    public function scopeInventoryByVendorLocation($query, $vendor_id = null)
    {
        $location_inventory = self::select(
            'vendors.id as vendor_id',
            'vendors.name as vendor_name',
            'batch_location.approved',
            'locations.name as location',
            DB::raw('SUM(batch_location.quantity) as inventory'),
            DB::raw('(SUM(batch_location.quantity * batch_location.unit_price))/100 as inventory_value')
        )
            ->join('batch_location', 'batches.id', '=', 'batch_location.batch_id')
            ->join('orders', 'batches.purchase_order_id', '=', 'orders.id')
            ->join('locations', 'batch_location.location_id', '=', 'locations.id')
            ->join('users as vendors', 'orders.vendor_id', '=', 'vendors.id')
            ->groupBy('batch_location.unit_price')
            ->groupBy('batch_location.approved')
            ->groupBy('location')
            ->groupBy('vendor_id')
            ->groupBy('vendor_name');

        if ($vendor_id) {
            $location_inventory->where('orders.vendor_id', $vendor_id);
        }

        return $location_inventory;

    }

    public function isTopParent()
    {
        return $this->top_level_parent->id == $this->id;
    }

    public function isChild()
    {
        return  ! $this->isTopParent();
    }

    public function submitForTesting($sample_size_grams, $ref_number, $package_date, $testing_laboratory_id)
    {
        //source gram cost
        $this_gram_cost = ($this->uom == 'lb' ? $this->unit_price / config('inventorymgmt.uom.lb') : $this->unit_price);

        $sample_batch = self::create([
            'parent_id' => $this->id,
            'category_id' => 30,
            'status' => 'Lab',
            'testing_status' => 'Submitted',
            'name' => $this->name,
            'ref_number' => $ref_number,
            'units_purchased' => $sample_size_grams,
            'inventory' => $sample_size_grams,
            'unit_price' => $this_gram_cost,
            'subtotal_price' => ($this_gram_cost * $sample_size_grams),
            'tax' => 0,
            'packaged_date' => $package_date,
        ]);

        $xfer_log = TransferLog::create([
            'user_id' => Auth::user()->id,
            'batch_id' => $this->id,
            'quantity_transferred' => ($this->uom == 'lb' ? $sample_size_grams / config('inventorymgmt.uom.lb') : $sample_size_grams),
            'start_wt_grams' => $sample_size_grams,
            'packer_name' => Auth::user()->name,
            'type' => 'Pre-Pack',
            'reason' => 'Lab Test Sample',
        ]);

        $xfer_log->transfer_log_details()->create([
            'batch_id' => $sample_batch->id,
            'action' => 'Created',
            'units' => $sample_size_grams,
        ]);

        $this->testing_laboratory_id = $testing_laboratory_id;
        $this->status = 'Inventory';
        $this->testing_status = 'In-Testing';
        $this->tested_at = Carbon::now();
        $this->coa_batch = 1;

        $this->inventory = $this->inventory - ($this->uom == 'lb' ? $sample_size_grams / config('inventorymgmt.uom.lb') : $sample_size_grams);
        $this->transfer = $this->transfer + ($this->uom == 'lb' ? $sample_size_grams / config('inventorymgmt.uom.lb') : $sample_size_grams);

        $this->save();

        return $sample_batch->refresh();
    }

    public function reconcile($change_to, $current_inventory, $location_id, $unit_cost, $reason, $notes = null, )
    {
        if (is_null($current_inventory)) {
            throw new \Exception("There is no current inventory value.");
        }

        $loss_qty = (float)bcsub($current_inventory, $change_to, 4);
        if($loss_qty==0) return false;

        $loss_grams = $loss_qty;
        $loss_cost = ($unit_cost * $loss_qty);

        $transfer_log_data = [
            'user_id' => Auth::user()->id,
            'batch_id' => $this->id,
            'location_id' => $location_id,
            'quantity_transferred' => $loss_qty,
            'unit_cost' => $unit_cost,
            'inventory_loss' => $loss_cost,
            'inventory_loss_grams' => $loss_grams,
            'packer_name' => 'Reconcile',
            'type' => 'Reconcile',
            'reason' => $reason,
            'notes' => $notes,
        ];

        $transfer_log = TransferLog::create($transfer_log_data);

        $this->inventory += ($loss_qty * -1);

        BatchLocation::create([
            'batch_id' => $this->id,
            'location_id' => $location_id,
            'transfer_log_id' => $transfer_log->id,
            'quantity' => ($loss_qty * -1),
//            'name' => $this->present()->branded_name,
            'unit_price' => $unit_cost,
            'approved' => 1,
            'approved_at' => Carbon::now(),
        ]);

//        if ($loss_cost != 0) {
//
//            //cog account
//            if (Auth::user()->hasLocation()) {
//                $cog_account = Auth::user()->current_location->cog_account()->journal;
//            } else {
//                $cog_account = ChartOfAccount::COG()->journal;
//            }
//
//            $loss_cost_money = convert_to_cents($loss_cost, true, true);
//
//            $transaction_group = Accounting::newDoubleEntryTransactionGroup();
//            $transaction_group->addTransaction($cog_account, ($loss_cost > 0 ? 'debit' : 'credit'), $loss_cost_money);
//            $transaction_group->addTransaction(ChartOfAccount::Inventory()->journal, ($loss_cost > 0 ? 'credit' : 'debit'), $loss_cost_money);
//            $transaction_group->commit();
//        }

        $this->save();

        return true;
    }

    public function getOriginalNameAttribute()
    {
        if ($this->name != $this->getOriginal('name')) {
            return $this->getOriginal('name');
        }

        return '';
    }

    public function getNameAttribute($value)
    {
//        if (Auth::user()->hasLocation() && $this->allocated_inventory->count()) {
//            return $this->allocated_inventory->first()->batch_location->name;
//        }

        return $value;
    }

    public function getLocationValueAttribute()
    {
//        if(!$this->location_unit_price) return 0;
        return ($this->unit_price * $this->onhand_inventory);
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['unit_price'] = (float)$value * 100;
    }

    public function getUnitPriceAttribute($value)
    {
        if($this->relationLoaded('locations_aggregate') && $this->locations_aggregate->count() == 1) {
            if($this->locations_aggregate->first()->batch_location_aggregate->location_unit_price) {
                return $this->locations_aggregate->first()->batch_location_aggregate->location_unit_price;
            }
        }

        if (!is_null($this->location_unit_price)) {
            return $this->location_unit_price;
        } elseif(!is_null($this->avg_unit_price)) {
            return $this->avg_unit_price;
        }

        return $value / 100;
    }

    public function getOriginalUnitPriceAttribute()
    {
        return $this->getRawOriginal('unit_price')/100;
    }

    public function setAvgUnitPriceAttribute($value)
    {
        $this->attributes['avg_unit_price'] = (float)$value * 100;
    }

    public function getAvgUnitPriceAttribute($value)
    {
        if($value) return $value / 100;

        return $this->original_unit_price;
    }

    public function getLocationUnitPriceAttribute($value)
    {
        if($value) return $value / 100;
    }


    public function getAvailableInventoryAttribute($value)
    {
        if(!is_null($value)) return $value;

        if(!$this->relationLoaded('locations_aggregate') || $this->locations_aggregate->count() > 1) {
            throw new \Exception("Unable to determine Available Inventory");
        }

        return $this->locations_aggregate->first()->batch_location_aggregate->available_inventory;
    }

    public function getOnhandInventoryAttribute($value)
    {
        if(!is_null($value)) return $value;

        if(!$this->relationLoaded('locations_aggregate') || $this->locations_aggregate->count() > 1) {
            throw new \Exception("Unable to determine On-hand Inventory");
        }

        return $this->locations_aggregate->first()->batch_location_aggregate->onhand_inventory;
    }

    public function getPendingInventoryAttribute($value)
    {
        if(!is_null($value)) return $value;

        if(!$this->relationLoaded('locations_aggregate') || $this->locations_aggregate->count() > 1) {
            throw new \Exception("Unable to determine Pending Inventory");
        }

        return $this->locations_aggregate->first()->batch_location_aggregate->pending_inventory;
    }

    public function getApprovedInventoryAttribute($value)
    {
        if(!is_null($value)) return $value;

        if(!$this->relationLoaded('locations_aggregate') || $this->locations_aggregate->count() > 1) {
            throw new \Exception("Unable to determine Approved Inventory");
        }

        return $this->locations_aggregate->first()->batch_location_aggregate->approved_inventory;
    }

    public function getWaitingApprovalInventoryAttribute($value)
    {
        if(!is_null($value)) return $value;

        if(!$this->relationLoaded('locations_aggregate') || $this->locations_aggregate->count() > 1) {
            throw new \Exception("Unable to determine Approved Inventory");
        }

        return $this->locations_aggregate->first()->batch_location_aggregate->waiting_approval_inventory;
    }

    public function getTotalAvailableInventoryAttribute()
    {
        if($this->relationLoaded('locations_aggregate')) {
            return $this->locations_aggregate->sum('batch_location_aggregate.available_inventory');
        }
        return 0;
    }

    public function setTotalInventoryValueAttribute($value)
    {
        $this->attributes['total_inventory_value'] = (float)$value * 100;
    }

    public function getTotalInventoryValueAttribute($value)
    {
        return $value / 100;
    }

    public function setSubtotalPriceAttribute($value)
    {
        $this->attributes['subtotal_price'] = (float)$value * 100;
    }

    public function getSubtotalPriceAttribute($value)
    {
        return $value / 100;
    }

    public function setSuggestedUnitSalePriceAttribute($value)
    {
        $this->attributes['suggested_unit_sale_price'] = (float)$value * 100;
    }

    public function getSuggestedUnitSalePriceAttribute($value)
    {
        if($this->relationLoaded('locations_aggregate') && $this->locations_aggregate->count() == 1) {
            return $this->locations_aggregate->first()->batch_location_aggregate->suggested_unit_sale_price;
        }

//        if (Auth::user()->hasLocation() && $this->allocated_inventory->count()) {
//            return $this->allocated_inventory->first()->batch_location->suggested_unit_sale_price;
//        }

        return $value / 100;
    }

    public function setMinFlexAttribute($value)
    {
        $this->attributes['min_flex'] = (float)$value * 100;
    }

    public function getMinFlexAttribute($value)
    {
        if($this->relationLoaded('locations_aggregate') && $this->locations_aggregate->count() == 1) {
            return $this->locations_aggregate->first()->batch_location_aggregate->min_flex;
        }

        return $value / 100;
    }

    public function getMinFlexPriceAttribute()
    {
        return $this->suggested_unit_sale_price - $this->min_flex;
    }

    public function setMaxFlexAttribute($value)
    {
        $this->attributes['max_flex'] = (float)$value * 100;
    }

    public function getMaxFlexAttribute($value)
    {
        return $value / 100;
    }

    public function getMaxFlexPriceAttribute()
    {
        return $this->suggested_unit_sale_price + $this->max_flex;
    }

    public function setTaxAttribute($value)
    {
        $this->attributes['tax'] = (float)$value * 100;
    }

    public function getTaxAttribute($value)
    {
        return $value / 100;
    }

    public static function createBatch($data, $location_id)
    {

        if( ! isset($data['track_inventory'])) {
            $data['track_inventory'] = 1;
        }

        if( ! $data['track_inventory']) {
            $data['quantity']=0;
            $data['uom']='Unit';
        }

        $batchObj = parent::create([
            'purchase_order_id' => ($data['purchase_order_id']??null),
            'category_id' => $data['category_id'],
            'brand_id' => $data['brand_id'],
            'fund_id' => 1,
            'status' => 'Inventory',
            'name' => $data['name'],
            'description' => ! empty($data['description']) ? $data['description'] : null,
            'type' => ! empty($data['type']) ? $data['type'] : null,
            'ref_number' => (! empty($data['ref_number']) ? str_replace('/', '-', $data['ref_number']) : implode('-', ['BT'.Carbon::now()->format('ny'), mt_rand(10000, 99999)])),
            'track_inventory' => $data['track_inventory'],
            'units_purchased' => $data['quantity'],
            'unit_price' => $data['unit_cost'],
            'subtotal_price' => ($data['quantity'] * $data['unit_cost']),
            'suggested_unit_sale_price' => $data['suggested_unit_sale_price'],
            'min_flex' => $data['min_flex'],
            'inventory' => $data['quantity'],
            'uom' => $data['uom'],
        ]);

        $batchObj->allocate(null,
            $location_id,
            $data['quantity'],
            ($data['allocated_name'] ?? $data['name']),
            $data['unit_cost'],
            $data['suggested_unit_sale_price'],
            $data['min_flex'],
            1
        );
    }

    public function allocate($origin_location_id, $destination_location_id, $quantity, $name = null, $unit_price = null, $suggested_unit_sale_price = null, $min_flex = null, $approved = null)
    {

        $this->origin_location_id = $origin_location_id;
        $this->destination_location_id = $destination_location_id;

        if ($origin_location_id) { // transferring batches from one location to another
            $data = [
                'batch_id' => $this->id,
                'location_id' => $origin_location_id,
                'quantity' => ($quantity * -1),
                'name' => $name ?: $this->name,
                'unit_price' => ($unit_price ?: $this->unit_price)*100,
                'suggested_unit_sale_price' => ($suggested_unit_sale_price ?: $this->suggested_unit_sale_price)*100,
                'min_flex' => ($min_flex ?: $this->min_flex)*100,
                'approved' => 1,
                'approved_at'=>Carbon::now(),
                'created_at'=>Carbon::now(),
                'updated_at'=>Carbon::now(),
            ];
//dd($data);

            $reverse_batch_location_id = BatchLocation::insertGetId($data);

            $parent_id = $reverse_batch_location_id;
        }

        $data_aggregate_default = [
            'location_batch_name' => $name ?: $this->name,
            'suggested_unit_sale_price' => $suggested_unit_sale_price ?: $this->suggested_unit_sale_price,
            'min_flex' => $min_flex ?: $this->min_flex,
        ];

//        dump($data_aggregate_default);
        $this->locations_aggregate()->syncWithoutDetaching([$destination_location_id => $data_aggregate_default]);

        $data = [
            'quantity' => $quantity,
            'name' => $name ?: $this->name,
            'unit_price' => $unit_price ?: $this->unit_price,
            'suggested_unit_sale_price' => $suggested_unit_sale_price ?: $this->suggested_unit_sale_price,
            'min_flex' => $min_flex ?: $this->min_flex,
        ];

        if($approved) {
            $data['approved'] = 1;
            $data['price_approved'] = 1;
            $data['approved_at'] = Carbon::now();
        }

        if (! empty($parent_id)) {
            $data['parent_id'] = $parent_id;
        }
//dd($data);
        $this->locations()->attach([$destination_location_id => $data]);

        event(new BatchAllocated($this, $data));
    }

    public function average_unit_cost()
    {

        $this->load('locations');

        $location_rows = $this->locations()->whereNull('transfer_log_id')->get();
        $location_inventory = $location_rows->sum('batch_location.quantity');
        $location_value = $location_rows->sum('batch_location.line_cost');

//        dump('loc value');
//        dump($this->locations);
//        dump($location_inventory);
//        dump($location_value);

        $sold_inventory = $this->order_details_sold->sum('final_units');
        $sold_cost = $this->order_details_sold->sum('cost');

//         get value + qty of sold inventory
//        dump('sold value');
//        dump($sold_inventory);
//        dump($sold_cost);

//        dump('total inventory purchased');
//        dump($this->units_purchased);
//        dump($location_inventory + $sold_inventory);
//
//        dump('total value');
//        dump($location_value + $sold_cost);

        $total_inventory = ($location_inventory + $sold_inventory);
        $total_value = ($location_value + $sold_cost);

//        dump('avg cost');
//        dump($total_value / $total_inventory);
//
//        dd('end');
        if(!$total_inventory) return 0;
        return ($total_value / $total_inventory);

    }

    public function unitCostAtLocation($location_id)
    {
//        dd($this->locations_aggregate->firstWhere('id', $location_id));
        return $this->locations_aggregate->firstWhere('id', $location_id)->batch_location_aggregate;
    }

    public function getCostByLocationAttribute()
    {
        $cost_by_location = collect();

        $locations = Location::withTrashed()->get();

        $allocations_group_by_location_id = $this->locations()->whereNull('transfer_log_id')->get()->groupBy('id');

        //dd($locations);
//        dd($allocations_group_by_location_id);

        $locations->each(function($location) use ($cost_by_location, $allocations_group_by_location_id) {

            if(!empty($allocations_group_by_location_id[$location->id])) {

                $location_collection = $allocations_group_by_location_id[$location->id];

//                dump($location_collection->count());
//                dump($location_collection->sum('batch_location.quantity'));
//                dump($location_collection->sum('batch_location.line_cost'));

                $location_inventory = $location_collection->sum('batch_location.quantity');
                $location_value = $location_collection->sum('batch_location.line_cost');

                $cost_by_location->put($location->id, ($location_inventory?($location_value/$location_inventory):0));

            } else {
                $cost_by_location->put($location->id, $this->original_unit_price);
            }
        });

//dd($cost_by_location);
        return $cost_by_location;
    }

}
