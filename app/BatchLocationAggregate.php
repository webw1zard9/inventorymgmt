<?php

namespace App;

use App\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;

class BatchLocationAggregate extends Pivot
{
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new LocationScope);
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function setLocationUnitPriceAttribute($value)
    {
        $this->attributes['location_unit_price'] = $value * 100;
    }

    public function getLocationUnitPriceAttribute($value)
    {
        return $value / 100;
    }

    public function setSuggestedUnitSalePriceAttribute($value)
    {
        $this->attributes['suggested_unit_sale_price'] = $value * 100;
    }

    public function getSuggestedUnitSalePriceAttribute($value)
    {
        return $value / 100;
    }

    public function setMinFlexAttribute($value)
    {
        $this->attributes['min_flex'] = $value * 100;
    }

    public function getMinFlexAttribute($value)
    {
        return $value / 100;
    }

    public static function inventory_values($vendor_id = null)
    {
        $builder = self::select([
            'purchase_order.vendor_id',
            'locations.name AS inventory_location_name',
            DB::raw('SUM(batch_location_aggregate.onhand_cost / 100) AS onhand_cost'),
            DB::raw('SUM(batch_location_aggregate.available_cost / 100) AS available_cost'),
            DB::raw('SUM(batch_location_aggregate.pending_cost / 100) AS pending_cost'),
            DB::raw('SUM(batch_location_aggregate.fulfilled_cost / 100) AS fulfilled_cost'),
        ])
            ->join('batches', 'batches.id', '=', 'batch_location_aggregate.batch_id')
            ->join('locations', 'batch_location_aggregate.location_id', '=', 'locations.id')
            ->join('orders AS purchase_order', 'batches.purchase_order_id', '=', 'purchase_order.id')
            ->groupBy('purchase_order.vendor_id')
            ->groupBy('locations.name')
            ->having(DB::raw("SUM(batch_location_aggregate.onhand_cost / 100)"), '!=', 0)
            ->orHaving(DB::raw("SUM(batch_location_aggregate.available_cost / 100)"), '!=', 0)
            ->orHaving(DB::raw("SUM(batch_location_aggregate.pending_cost / 100)"), '!=', 0)
            ->orHaving(DB::raw("SUM(batch_location_aggregate.fulfilled_cost / 100)"), '!=', 0);

        if($vendor_id) {
            $builder->where('purchase_order.vendor_id', $vendor_id);
        }

        return $builder;

    }
}
