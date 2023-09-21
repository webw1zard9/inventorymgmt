<?php

namespace App;

use App\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransferLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'packed_date' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new LocationScope);
    }

    public function scopeFilters($query, $filters)
    {
        return $filters->apply($query);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function batch_converted()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function batches_converted()
    {
        return $this->hasMany(Batch::class, 'batch_id');
    }

    public function transfer_log_details()
    {
        return $this->hasMany(TransferLogDetail::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function transfer_log_detail()
    {
        return $this->belongsTo(TransferLogDetail::class);
    }

    public function setUnitCostAttribute($value)
    {
        $this->attributes['unit_cost'] = $value * 100;
    }

    public function getUnitCostAttribute($value)
    {
        return $value / 100;
    }

    public function setInventoryLossAttribute($value)
    {
        $this->attributes['inventory_loss'] = $value * 100;
    }

    public function getInventoryLossAttribute($value)
    {
        return $value / 100;
    }

    public function setShortageAttribute($value)
    {
        $this->attributes['shortage'] = $value * 100;
    }

    public function getShortageAttribute($value)
    {
        return $value / 100;
    }

    public function getCanUndoAttribute()
    {
        $canundo = true;

        foreach ($this->transfer_log_details as $transfer_log_detail) {
            $batch_created = $transfer_log_detail->batch_created;

            if (! $batch_created->inventory || ($batch_created->units_purchased != $batch_created->inventory)) {
                $canundo = false;
                break;
            }
        }

        return $canundo;
    }

    public static function reconciliations($date_range)
    {
        return self::whereNull('location_id')
                    ->whereDate('created_at', '>=', $date_range[0])
                    ->whereDate('created_at', '<=', $date_range[1])
        ->get();
    }

    public function scopeReconciliationLog($query, $filters=null, $with=[], $batch=null)
    {
        $query
            ->select('transfer_logs.*')
            ->where('transfer_logs.type', 'Reconcile')
            ->orderBy('transfer_logs.created_at', 'desc');

        if ($batch->exists) {
            $query->where('transfer_logs.batch_id', $batch->id);
        }

        if($filters) {
            $query->filters($filters);
        }

        if($with) {
            $query->with($with);
        }

        return $query;
    }

    public function scopeCurrentReconciledCost($query)
    {
        return $query->select([
            'purchase_order.vendor_id',
            DB::raw('IFNULL(locations.name, "Main") AS reconciled_location_name'),
            DB::raw('SUM(transfer_logs.inventory_loss / 100) AS reconciled_cost')
        ])
            ->leftJoin('locations', 'transfer_logs.location_id', '=', 'locations.id')
            ->join('batches', 'transfer_logs.batch_id', '=', 'batches.id')
            ->join('orders AS purchase_order', 'batches.purchase_order_id', '=', 'purchase_order.id')
            ->join('accounting_journals', 'accounting_journals.morphed_id', '=', 'purchase_order.id')
            ->where('accounting_journals.morphed_type', '=', 'App\\PurchaseOrder')
            ->where('accounting_journals.balance', '!=', '0')
            ->groupBy('purchase_order.vendor_id')
            ->groupBy('locations.name')
            ->having(DB::raw('SUM(transfer_logs.inventory_loss / 100)'), '>', 0)
            ->orderBy('purchase_order.vendor_id')
            ->orderBy('locations.name');
    }

    public function scopeInventoryLoss($query)
    {
        return $query
            ->select(
                \DB::raw('DATE_FORMAT(transfer_logs.created_at, "%Y-01-%m") as packed_month_year'),
                'transfer_logs.type',
                \DB::raw('SUM(inventory_loss) as inventory_loss'),
                \DB::raw('SUM(shortage) as shortage'),
                'reason',
                'funds.name as fund_name'
            )
            ->leftJoin('batches', 'transfer_logs.batch_id', '=', 'batches.id')
            ->leftJoin('funds', 'batches.fund_id', '=', 'funds.id')
            ->groupBy(\DB::raw('DATE_FORMAT(transfer_logs.created_at, "%Y-01-%m")'))
            ->groupBy('transfer_logs.type')
            ->groupBy('reason')
            ->groupBy('funds.id')
            ->orderBy('packed_month_year', 'desc');
    }

    public function undo()
    {
        $batch_converted = $this->batch_converted;

        if ($this->quantity_transferred > 0 ||
            ($this->quantity_transferred < 0 && $batch_converted->inventory >= ($this->quantity_transferred * -1))) {
            $batch_converted->inventory += $this->quantity_transferred;
            $batch_converted->save();
            $this->delete();

            return true;
        } else {
            return false;
        }
    }

    public function storePackagingLoss(Batch $batch)
    {
        $log = self::create([
            'user_id' => Auth::user()->id,
            'batch_id' => $batch->id,
            'quantity_transferred' => 0,
            'inventory_loss' => $batch->unit_price,
            'packer_name' => 'System',
            'type' => 'Packaging',
        ]);
    }
}
