<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Scottlaurent\Accounting\Models\JournalTransaction;
use Spatie\Activitylog\Models\Activity;

class OrderDetail extends Model
{
    protected $guarded = [];

    protected $with = [
//        'sale_order',
//        'batch',
//        'batch.category',
//        'batch.fund',
//        'batch.allocated_inventory',
//        'batch.parent_batch',
//        'batch.fund',
//        'batch_location',
//        'fulfill_activity_log.causer',
    ];

    public function sale_order()
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id');
    }

    public function return_order()
    {
        return $this->belongsTo(Order::class, 'return_order_id');
    }

    public function batch()
    {
        $builder = $this->belongsTo(Batch::class);

//        if (Auth::user()->hasLocation()) {
//            $builder->addSelect([
//                'batches.*',
//                DB::raw('SUM(batch_location.quantity * batch_location.unit_price)/SUM(batch_location.quantity) as location_unit_price'),
//                DB::raw('SUM(batch_location.quantity) as location_quantity_available')
//            ])
//                ->join('batch_location', 'batches.id', '=', 'batch_location.batch_id')
//                ->where('batch_location.location_id', '=', Auth::user()->current_location->id)
//                ->groupby('batches.id');
//        }
        return $builder;
    }

    public function order_detail_returned()
    {
        return $this->hasMany(OrderDetail::class, 'parent_id');
    }

    public function parent_order_detail(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OrderDetail::class, 'parent_id');
    }


    public function batch_location(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BatchLocation::class);
    }

    public function batch_location_requires_discount_approval(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(BatchLocation::class)
            ->where('price_approved', 0);
    }

    public function journal_transaction(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JournalTransaction::class, 'acct_journal_txn_fid', 'acct_journal_txn_pid');
    }

    public function location(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function fulfill_activity_log()
    {
        return $this->morphOne(Activity::class, 'subject')
            ->where('description', 'Fulfill Item')
            ->orderBy('id', 'desc');
    }

    public function getTxnPostDateAttribute()
    {
        return (new Carbon($this->journal_transaction->post_date))->format('m/d/Y');
    }

    public function getFinalUnitsAttribute()
    {
        return ! is_null($this->units_accepted) ? $this->units_accepted : $this->units;
    }

    public function setUnitCostAttribute($value)
    {
        $this->attributes['unit_cost'] = (float)$value * 100;
    }

    public function getUnitCostAttribute($value)
    {
        return $value / 100;
    }

    public function setUnitSalePriceAttribute($value)
    {
        $this->attributes['unit_sale_price'] = (float)$value * 100;
    }

    public function getUnitSalePriceAttribute($value)
    {
        return $value / 100;
    }

    public function getCostAttribute()
    {
//        $multiplier = ($this->sale_order->status == 'returned' && $this->cog==1 ? -1 : 1 );
        $multiplier = 1;

        return $this->unit_cost * (! is_null($this->units_accepted) ? $this->units_accepted : $this->units) * $multiplier;
    }

    public function getLineCostAttribute()
    {
        return $this->cost;
    }

    public function getRevenueAttribute()
    {
        if (! $this->sale_order->hasRevenue()) {
            return 0;
        }

        $multiplier = ($this->sale_order->status == 'returned' && $this->cog == 1 ? -1 : 1);
        $multiplier = 1;

        return ($this->unit_sale_price * $this->units_accepted) * $multiplier;
    }

    public function getSalePriceAttribute()
    {
        return $this->unit_sale_price * $this->units;
    }

    public function getLineItemSubtotalAttribute()
    {
//        return ($this->unit_sale_price * abs( ! is_null($this->units_accepted) ? $this->units_accepted : $this->units));
        return $this->unit_sale_price * (! is_null($this->units_accepted) ? $this->units_accepted : $this->units);
    }

    public function getSubtotalAttribute()
    {
//        $multiplier = ($this->sale_order->status == 'returned' && $this->cog==1 ? -1 : 1 );
        $multiplier = 1;
        $units = (! is_null($this->units_accepted) ? $this->units_accepted : $this->units);

        return ((float)$units * $this->unit_sale_price) * $multiplier;
    }
    public function getNeedsApprovalAttribute()
    {
        return (bool) ($this->batch_location && ! $this->batch_location->price_approved);
    }

    public function getLineUnitDiscountAttribute()
    {
        return $this->unit_sale_price - $this->batch->suggested_unit_sale_price;
    }

    public function getLineUnitDiscountPctAttribute()
    {
        $markup = $this->batch->suggested_unit_sale_price - $this->unit_cost;

        return ($this->line_discount / $markup) * -100;
    }

    public function getLineDiscountAttribute()
    {
        return ($this->unit_sale_price - $this->batch->suggested_unit_sale_price) * $this->units;
    }

    public function getUnitMarginAttribute()
    {
        return $this->unit_sale_price - $this->unit_cost;
    }

    public function getMarkupPctAttribute()
    {
        if ($this->unit_cost == 0) {
            return '100%';
        }

        return number_format(($this->unit_margin / $this->unit_cost) * 100, 2).'%';
    }

    public function getMarginActualAttribute()
    {
        $m = $this->unit_margin * (! is_null($this->units_accepted) ? $this->units_accepted : 0);

        return $m + $this->order_detail_returned->sum('margin_actual');
    }

    public function getMarginAttribute()
    {
        return $this->unit_margin * (! is_null($this->units_accepted) ? $this->units_accepted : $this->units);
    }

    public function getMarginPctAttribute()
    {
        if (! $this->unit_sale_price) {
            return -100;
        }

        return number_format((($this->unit_margin / $this->unit_sale_price) * 100), 2);
    }

    public function getMarginPctActualAttribute()
    {
        if (! $this->unit_sale_price) {
            return -100;
        }
//        $m = $this->unit_margin * ( ! is_null($this->units_accepted) ? $this->units_accepted : 0 );
        return number_format((($this->unit_margin / $this->unit_sale_price) * 100), 2);
    }

    public function notAccepted()
    {
        return is_null($this->units_accepted);
    }



    public function isCOG()
    {
        return $this->cog == 1;
    }
}
