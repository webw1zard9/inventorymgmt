<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TransferLogDetail extends Model
{
    protected $guarded = [];

    public function batch_created()
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function transfer_log()
    {
        return $this->belongsTo(TransferLog::class)->with('batch_converted');
    }
}
