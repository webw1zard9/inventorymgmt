<?php

namespace App;

use App\Presenters\PresentableTrait;
use App\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Traits\LogsActivity;

class BatchLocation extends Pivot
{
    use LogsActivity, PresentableTrait;

    protected $presenter = \App\Presenters\BatchLocation::class;

    protected static $logAttributes = ['*'];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new LocationScope);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function order_detail()
    {
        return $this->belongsTo(OrderDetail::class);
    }

    public function transfer_log()
    {
        return $this->belongsTo(TransferLog::class);
    }

    public function parent_batch_location()
    {
        return $this->belongsTo(BatchLocation::class, 'parent_id')->withoutGlobalScope(new LocationScope);
    }

    public function intake_activity()
    {
        return $this->morphOne(Activity::class, 'subject')->where('log_name', 'inventory-intake-approved');
    }

    public function child_batch_location()
    {
        return $this->hasOne(BatchLocation::class, 'parent_id')->withoutGlobalScope(new LocationScope);
    }

    public static function updateAll($batch_id, $location_id, $data)
    {
        $bls = self::where('batch_id', $batch_id)
            ->where('location_id', $location_id)
            ->get();

        foreach ($bls as $bl) {
            $bl->update($data);
        }

        return true;
    }

    public function getApprovedStatusAttribute()
    {
        return ($this->approved?"Approved":"Pending");
    }

    public function getLineCostAttribute()
    {
        return $this->quantity * (is_null($this->unit_price) ? $this->batch->unit_price : $this->unit_price);
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['unit_price'] = $value * 100;
    }

    public function getUnitPriceAttribute($value)
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

    public function scopeFilters($query, $filters)
    {
        return $filters->apply($query);
    }

    public function scopeNeedApproval($builder)
    {
        $builder->where('price_approved', 0)
            ->with([
                'location',
                'batch',
                'order_detail.batch',
                'order_detail.sale_order.customer',
                'order_detail.sale_order.sales_rep',
            ])
        ->orderBy('created_at');

        if (Auth::check() && Auth::user()->current_location->exists) {
            $builder->where('location_id', '=', Auth::user()->current_location->id);
        }

        return $builder;
    }

    public function scopeNeedIntakeApproval($builder, $filters = null)
    {
        $builder
            ->select('batch_location.*')
            ->where('approved', 0)
            ->with([
                'location',
                'batch.category',
                'parent_batch_location.location',
                'child_batch_location.location',
                'order_detail.sale_order.customer',
            ])
            ->orderBy('batch_location.created_at');

        if (Auth::check() && Auth::user()->current_location->exists) {
            $builder->where('location_id', '=', Auth::user()->current_location->id);
        }

        if ($filters) {
            $builder->filters($filters);
        }

        return $builder;
    }
}
