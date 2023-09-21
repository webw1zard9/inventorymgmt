<?php

namespace App;

use App\Scopes\LocationScope;
use Illuminate\Database\Eloquent\Model;
use Scottlaurent\Accounting\Models\JournalTransaction;

class OrderTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'txn_date' => 'datetime',
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

//    public function scopePaid($query)
//    {
//        return $query->with([
//            'user',
//            'journal_transaction.journal.morphed',
//            'purchase_order.vendor',
//            'signature',
//            'location',
//        ])
//            ->whereIn('order_transactions.type', ['paid','refund'])
//            ->whereNotNull('purchase_order_id')
//            ->where('order_transactions.payment_method', '!=', 'Vendor Credit')
//            ->orderBy('order_transactions.created_at', 'desc');
//    }

    public function setAmountAttribute($value)
    {
        $this->attributes['amount'] = $value * 100;
    }

    public function getAmountAttribute($value)
    {
        return $value / 100;
    }

    public function setTxnFeeAttribute($value)
    {
        $this->attributes['txn_fee'] = $value * 100;
    }

    public function getTxnFeeAttribute($value)
    {
        return $value / 100;
    }

    public function getTotalAmountAttribute()
    {
        return $this->amount + $this->txn_fee;
    }

    public function parent()
    {
        return $this->belongsTo(OrderTransaction::class, 'parent_id');
    }

    public function child()
    {
        return $this->hasOne(OrderTransaction::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OrderTransaction::class, 'parent_id');
    }

    public function signature()
    {
        return $this->hasOne(OrderTransactionSignatures::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function purchase_order()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function sale_order()
    {
        return $this->belongsTo(SaleOrder::class, 'sale_order_id');
    }

    public function return_order()
    {
        return $this->belongsTo(SaleOrder::class, 'return_order_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class)->withTrashed();
    }

    public function txn_date()
    {
        $field = (! $this->txn_date ? 'created_at' : 'txn_date');

        return $this->{$field}->format('m/d/y');
    }

    public function journal_transaction()
    {
        return $this->belongsTo(JournalTransaction::class, 'acct_journal_txn_fid', 'acct_journal_txn_pid');
    }
}
