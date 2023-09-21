<?php

namespace App;

use AjCastro\EagerLoadPivotRelations\EagerLoadPivotTrait;
use App\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Scottlaurent\Accounting\ModelTraits\AccountingJournal;

class Location extends Model
{
    use AccountingJournal, EagerLoadPivotTrait, SoftDeletes;

    protected $guarded = [];

    public function getJournalBalanceInDollarsAttribute()
    {
        $multiplier = (in_array($this->journal->ledger->type, ['asset', 'expense']) ? -1 : 1);

        return $this->journal->getCurrentBalanceInDollars() * $multiplier;
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function sale_orders()
    {
        return $this->hasMany(SaleOrder::class);
    }

    public function transfer_logs()
    {
        return $this->hasMany(TransferLog::class);
    }

    public function chart_of_accounts()
    {
        return $this->belongsToMany(ChartOfAccount::class, 'chart_of_account_location')->withTimestamps();
    }

    public function cash_account()
    {
        return $this->chart_of_accounts()->where('name', 'like', '%cash%')->first();
    }

    public function inventory_account()
    {
        return $this->chart_of_accounts()->where('name', 'like', '%inventory%')->first();
    }

    public function cog_account()
    {
        return $this->chart_of_accounts()->where('name', 'like', '%cost%')->first();
    }

    public function batches()
    {
        return $this->belongsToMany(Batch::class)
            ->as('batch_location')
            ->withPivot('id', 'parent_id', 'order_detail_id', 'quantity', 'name', 'unit_price',
                'suggested_unit_sale_price', 'min_flex', 'approved', 'price_approved', 'cost_change',
                'approved_at', 'transfer_log_id', 'return_item')
            ->using(BatchLocation::class)
            ->withTimestamps();
    }

    public function batches_aggregate()
    {
        return $this->belongsToMany(Batch::class, 'batch_location_aggregate')
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
            ->using(BatchLocationAggregate::class)
            ->withTimestamps();
    }

    public function remainingPoInventory($batch_id)
    {
        return self::batches()
            ->with('batch_location')
            ->where('batches.id', $batch_id)
            ->whereNull('transfer_log_id')
            ->sum('batch_location.quantity');
    }

    public function remainingInventory($batch_id)
    {
        return self::batches()
            ->with('batch_location')
            ->where('batches.id', $batch_id)
            ->where('batch_location.approved', 1)
            ->sum('batch_location.quantity');
    }

    public function hasInventory()
    {
        return self::batches_aggregate()->sum('onhand_inventory');
    }

    public static function profit_and_loss($date_range)
    {
        //get sales & reconcilations for locations
        $locations = self::with([
            'sale_orders' => function ($q) use ($date_range) {
                $q->where('status', 'delivered')
                    ->whereDate('delivered_at', '>=', $date_range[0])
                    ->whereDate('delivered_at', '<=', $date_range[1]);
            },
            'transfer_logs' => function ($q) use ($date_range) {
                $q->whereDate('created_at', '>=', $date_range[0])
                    ->whereDate('created_at', '<=', $date_range[1]);
            },
            'sale_orders.sales_rep',
            'sale_orders.order_details_cog.batch.category',

        ])->whereHas('sale_orders', function ($q) use ($date_range) {
            $q->where('status', 'delivered')
                ->whereDate('delivered_at', '>=', $date_range[0])
                ->whereDate('delivered_at', '<=', $date_range[1]);

            if (Auth::user()->hasLocation()) {
                $q->where('location_id', Auth::user()->current_location->id);
            }
        })->orWhereHas('transfer_logs', function ($q) use ($date_range) {
            $q->whereDate('created_at', '>=', $date_range[0])
                ->whereDate('created_at', '<=', $date_range[1]);

            if (Auth::user()->hasLocation()) {
                $q->where('location_id', Auth::user()->current_location->id);
            }
        })->withTrashed()->get();

        $locations->transform(function ($location, $key) {
            $location->total_order += $location->sale_orders->sum('subtotal');
            $location->total_discount += $location->sale_orders->sum('discount');
            $location->total_rev += $location->sale_orders->sum('total');
            $location->total_cog += $location->sale_orders->sum('cost');
            $location->total_profit += ($location->sale_orders->sum('total') - $location->sale_orders->sum('cost'));
            $location->total_order_count += $location->sale_orders->count();
            $location->total_reconciliation_cost += $location->transfer_logs->sum('inventory_loss');
            $location->total_reconciliation_profit -= $location->transfer_logs->sum('inventory_loss');
            return $location;
        });

        $locations->transform(function ($location, $key) {

            $location->total_cog += $location->transfer_logs->sum('inventory_loss');
            $location->total_profit += $location->transfer_logs->sum('inventory_loss')*-1;

            return $location;
        });

        return $locations;
    }

    public static function activeLocations()
    {
        $builder = self::where('active', 1)->orderBy('name');

        if (Auth::check() && Auth::user()->locations && ! Auth::user()->isAdmin()) {
            $builder->whereIn('id', Auth::user()->locations->pluck('id'));
        }

        return $builder;
    }

    public static function availableLocations()
    {
        $builder = self::orderBy('name')->withTrashed();

        if (Auth::check() && Auth::user()->locations && ! Auth::user()->isAdmin()) {
            $builder->whereIn('id', Auth::user()->locations->pluck('id'));
        }

        return $builder;
    }

    public function scopeIsActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopeInventoryValueByLocation($query)
    {
        $query->select([
            'locations.*',
            'locations.name',
            DB::raw('(SUM(batch_location.quantity * batch_location.unit_price)/SUM(quantity) * (IFNULL(SUM(batch_location.quantity), 0)))/100 as inventory_value'),
            'batch_location.approved',
        ])
            ->join('batch_location', 'locations.id', '=', 'batch_location.location_id')
            ->join('batches', 'batches.id', '=', 'batch_location.batch_id')
            ->where('batches.inventory', '>', 0)
//            ->having('inventory_value','>',0)
            ->groupBy('locations.id')
            ->groupBy('batches.id')
            ->groupBy('batch_location.approved');

        if (Auth::user()->hasLocation()) {
            $query->where('locations.id', Auth::user()->current_location->id);
        }

        return $query;
    }
}
