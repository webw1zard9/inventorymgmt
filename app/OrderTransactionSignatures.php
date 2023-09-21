<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderTransactionSignatures extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order_transaction()
    {
        return $this->belongsTo(OrderTransaction::class);
    }
}
