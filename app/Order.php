<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $table = 'orders';

    protected static $search_table = null;

    protected $casts = [
        'txn_date' => 'datetime',
        'due_date' => 'datetime',
        'expected_delivery_date' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function bill_to()
    {
        return $this->belongsTo(User::class, 'bill_to_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sales_rep()
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function broker()
    {
        return $this->belongsTo(User::class, 'broker_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function order_details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function order_details_cog()
    {
        return $this->hasMany(OrderDetail::class)->where('cog', 1)->with('sale_order');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(OrderTransaction::class);
    }

    public function origin_license()
    {
        return $this->belongsTo(License::class, 'origin_license_id');
    }

    public function destination_license()
    {
        return $this->belongsTo(User::class, 'destination_license_id');
    }

    /**
     * @param $query
     * @param $filters
     * @return mixed
     */
    public function scopeFilters($query, $filters)
    {
        return $filters->apply($query);
    }

    public function scopeOpenOrders($query)
    {
        return $query->where('orders.status', 'hold');
    }

    public function scopeDeliveredOrders($query)
    {
        return $query->where('orders.status', 'delivered');
    }

    public function scopeWithDateRange($query, $date, $col = 'delivered_at')
    {
        if (is_array($date)) {
            $query->whereDate('orders.delivered_at', '>=', $date[0])
                ->whereDate('orders.delivered_at', '<=', $date[1]);
        } else {
            $query->whereDate($date);
        }

        return $query;
    }

    public function scopeJoinBatchAndCategories($query)
    {
        return $query->join('order_details', 'orders.id', '=', 'order_details.sale_order_id')
            ->join('batches', 'order_details.batch_id', '=', 'batches.id')
            ->join('categories', 'batches.category_id', '=', 'categories.id');
    }

    /**
     * @param $value
     */
    public function setSubtotalAttribute($value)
    {
        $this->attributes['subtotal'] = $value * 100;
    }

    /**
     * @param $value
     * @return float
     */
    public function getSubtotalAttribute($value)
    {
        return $value / 100;
    }

    /**
     * @param $value
     */
    public function setDiscountAttribute($value)
    {
        $this->attributes['discount'] = $value * 100;
    }

    /**
     * @param $value
     * @return float
     */
    public function getDiscountAttribute($value)
    {
        return $value / 100;
    }

    /**
     * @param $value
     */
    public function setTaxAttribute($value)
    {
        $this->attributes['tax'] = $value * 100;
    }

    /**
     * @param $value
     * @return float
     */
    public function getTaxAttribute($value)
    {
        return $value / 100;
    }

    /**
     * @param $value
     */
    public function setTotalAttribute($value)
    {
        $this->attributes['total'] = $value * 100;
    }

    /**
     * @param $value
     * @return float
     */
    public function getTotalAttribute($value)
    {
        return $value / 100;
    }

    /**
     * @param $value
     */
    public function setBalanceAttribute($value)
    {
        $this->attributes['balance'] = $value * 100;
    }

    /**
     * @param $value
     * @return float
     */
    public function getBalanceAttribute($value)
    {
        if ($this->journal) {
            return $this->journal->balance->getAmount() / 100;
        } else {
            return $value / 100;
        }
    }

    public function setRunningBalanceAttribute($value)
    {
        $this->attributes['running_balance'] = $value * 100;
    }

    /**
     * @param $value
     * @return float
     */
    public function getRunningBalanceAttribute($value)
    {
        return $value / 100;
    }


    public function getDestinationLicenseNameLicAttribute()
    {
        if ($this->destination_license()->exists()) {
            return ($this->destination_license->user->id != $this->customer_id ? $this->destination_license->user->name.' ' : '').$this->destination_license->number.' - '.$this->destination_license->license_type->name;
        } else {
            return ucfirst($this->customer_type);
        }
    }

    /**
     * @param $amount
     * @param $txn_date
     * @return $this
     */
    public function applyPayment($amount, $txn_date, $payment_method, $ref_number, $memo, $txn_id, $location_id = null, $txn_fee = null, $parent_id = null, $type=null, $vendor_id=null)
    {
        $txn = new OrderTransaction();
        $txn->parent_id = $parent_id;
        $txn->vendor_id = $vendor_id??null;
        $txn->user_id = (Auth::user() ? Auth::user()->id : User::where('name','Admin')->first()->id);
        $txn->amount = $amount;
        $txn->txn_fee = ($txn_fee ?: null);
        $txn->type = ($type ?? ($amount < 0 ? 'refund' : $this->payment_type));
        $txn->txn_date = $txn_date;
        $txn->payment_method = $payment_method;
        $txn->ref_number = $ref_number;
        $txn->memo = $memo;
        $txn->acct_journal_txn_fid = $txn_id;
        $txn->location_id = $location_id;

//        if ($location_id) {
//            $txn->location_id = $location_id;
//        } else {
//            $txn->location_id = Auth::user()->current_location->id === 0 ? null : Auth::user()->current_location->id;
//        }
//        $this->balance = bcsub($this->balance, $txn->amount, 2);

        $this->transactions()->save($txn);

        if ($this instanceof PurchaseOrder && $this->balance == 0) {
            $this->status = 'closed';
        }

        $this->save();

        $activity_prop = collect([
            'Txn #' => $txn->id,
            'Parent Txn #' => $parent_id,
            'Amount' => display_currency($amount),
            'Method' => $payment_method,
            'Memo' => $memo,
        ]);

        if($this instanceof PurchaseOrder) {
            $log_name = 'purchase-order';
            $activity_prop->put('Ref #', $ref_number);
        } else {
            $log_name = 'sale-order';
            $activity_prop->put('Crypto', $ref_number);
        }

        activity($log_name)
            ->causedBy(Auth::user())
            ->performedOn($this)
            ->withProperties($activity_prop)
            ->log(ucwords($txn->type));

        return $txn;
    }

    public function scopeWithOutstandingBalance($query)
    {
        return $query->where('balance', '!=', 0)->orderBy('txn_date');
    }

    public function scopeWithPastDueBalance($query)
    {
        return $query->where('balance', '!=', 0)
            ->whereDate('due_date', '<', Carbon::today())
            ->orderBy('due_date');
    }

    public function new_ref_number($order_type)
    {
        return $order_type.Carbon::now()->format('ny').'-'.sprintf('%06d', $this->id);
    }

    public static function customer_type()
    {
        return static::select('customer_type')
            ->whereNotNull('customer_type')
            ->groupBy('customer_type');
    }

    public static function app_search($q)
    {
        return self::query()
            ->select('orders.*')
            ->join('users', 'orders.'.self::$search_table.'_id', '=', 'users.id')
            ->where(function ($qry) use ($q) {
                $qry->where('users.name', 'like', '%'.$q.'%')
                    ->orWhere('ref_number', 'like', '%'.$q.'%');
//                    ->orWhere(DB::raw('cast(json_unquote(json_extract(details, \'$.business_name\')) AS CHAR)'), 'like', '%'.$q.'%')
            })
            ->with(self::$search_table)
            ->orderBy('txn_date');
//
    }

    public function getPaymentTxns()
    {
        if (is_null($this->journal)) {
            return collect();
        }
        $payment_transactions = $this->journal->transactions()->whereNotNull($this->payment_type ? 'debit' : 'credit')->get();

        $payment_transactions->transform(function ($item, $key) {
            $item->debit = $item->debit / 100;

            return $item;
        });

        return $payment_transactions;
    }
}
