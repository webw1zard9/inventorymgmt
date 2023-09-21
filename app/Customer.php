<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 5/14/20
 * Time: 16:23
 */

namespace App;

use App\Scopes\CustomerScope;
use Scottlaurent\Accounting\ModelTraits\AccountingJournal;

class Customer extends User
{
    use AccountingJournal;

    protected $table = 'users';

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new CustomerScope());
    }

    public function licenses()
    {
        return $this->hasMany(License::class, 'user_id');
    }

    public static function all($columns = ['*'], $active = 1)
    {
//        parent::all();

        return (new static)
            ->newQuery()
            ->where('active', $active)
            ->orderBy('name')
            ->get(
                is_array($columns) ? $columns : func_get_args()
            );

//        return static::where('active',1)->get();
    }

    public function scopeWithBalance($query, $qry_clause = null, $sale_type = null)
    {
        return $query->select('users.*', \DB::raw('sum(orders.balance) as outstanding_balance'))
            ->leftjoin('orders', 'users.id', '=', 'orders.customer_id')
            ->with(['sale_orders' => function ($query) use ($qry_clause, $sale_type) {
                $query->where('balance', '!=', 0)
                    ->with('transactions')
                    ->with('sales_rep')
                    ->where('status', 'delivered')
                    ->orderBy('txn_date', 'desc');
                if ($qry_clause && $sale_type) {
                    $query->{$qry_clause}('sale_type', $sale_type);
                }
            }])
            ->where('orders.balance', '!=', 0)
            ->where('status', 'delivered')
            ->groupBy('users.id')
            ->orderBy('outstanding_balance', 'desc');
    }
}
