<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 2/19/19
 * Time: 08:57
 */

namespace App;

use App\Scopes\VendorScope;
use Illuminate\Support\Facades\DB;

class Vendor extends User
{
    protected $table = 'users';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new VendorScope);
    }

    public function getBalanceAttribute()
    {
//        $multiplier = (in_array($this->journal->ledger->type, ['asset', 'expense']) ? -1 : 1);
        return ($this->journal->balance->getAmount() / 100);
    }

    public function getPoTotalAttribute($value)
    {
        return $value / 100;
    }

    public function getPoBalanceAttribute($value)
    {
        return $value / 100;
    }

    public function licenses()
    {
        return $this->hasMany(License::class, 'user_id');
    }

    public static function all($columns = ['*'], $active = 1)
    {
        return (new static)
            ->newQuery()
            ->where('active', $active)
            ->orderBy('name')
            ->get(
            is_array($columns) ? $columns : func_get_args()
        );
    }

    public function scopeFilters($query, $filters)
    {
        return $filters->apply($query);
    }

    public function scopeWithBalance($query)
    {
        return $query->select('users.*', \DB::raw('sum(orders.balance) as outstanding_balance'))
            ->leftjoin('orders', 'users.id', '=', 'orders.vendor_id')
            ->with(['purchase_orders' => function ($query) {
                $query->where('balance', '!=', 0)
                    ->with('transactions')
                    ->orderBy('txn_date', 'desc')
                    ->orderBy('ref_number', 'desc');
            }])
            ->where('orders.balance', '!=', 0)
            ->groupBy('users.id')
            ->orderBy('outstanding_balance', 'desc');
    }

    public function scopeOwedForSoldInventory($query)
    {

//        vendors.name vendor_name, po.ref_number, locations.name location_name, batches.units_purchased, batches.inventory available_inventory, batches.name, batches.ref_number, order_details.units, order_details.units_accepted,
        // FORMAT((order_details.unit_cost * order_details.units_accepted)/100, 2) as owed_to_vendor, so.ref_number as sales_order, so.status, order_details.id

        $query->select([
            'users.id',
            'users.name as vendor_name',
            'po.ref_number as purchase_order_ref_number',
            'po.total as po_total',
            'po.balance as po_balance',
            'locations.name as location_name',
            'batches.units_purchased',
            'batches.inventory as available_inventory',
            'batches.name as batch_name',
            'batches.ref_number as batch_sku',
            'batches.uom',
            'batches.unit_price',
            'order_details.units as ordered_units',
            'order_details.units_accepted as sold_units',
            DB::raw('(order_details.unit_cost * order_details.units_accepted)/100 as owed_to_vendor'),
            'so.ref_number as sales_order_ref_number',
            'so.status as sales_order_status',
            'order_details.id as order_detail_id',
        ])
        ->leftjoin('orders as po', 'po.vendor_id', '=', 'users.id')
        ->join('batches', 'batches.purchase_order_id', '=', 'po.id')
        ->leftjoin('order_details', 'batches.id', '=', 'order_details.batch_id')
        ->leftjoin('orders as so', 'order_details.sale_order_id', '=', 'so.id')
        ->leftjoin('locations', 'so.location_id', '=', 'locations.id')
        ->orderBy('po.ref_number')
        ->orderBy('locations.name');

        return $query;
    }

}
