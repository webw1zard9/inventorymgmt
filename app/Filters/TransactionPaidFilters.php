<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 16:56
 */

namespace App\Filters;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionPaidFilters extends Filters
{
    protected $default_filters = [];

    protected $filters = [
        'date_preset',
        'from',
        'to',
    ];

    /**
     * BatchFilters constructor.
     *
     * @param  string  $cache_key
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->default_filters['from'] = Carbon::now()->format('Y-m-d');
        $this->default_filters['to'] = Carbon::now()->format('Y-m-d');
        $this->cache_key = 'transaction_paid_filters_'.Auth::user()->id;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function from()
    {
        return $this->builder->whereDate('order_transactions.txn_date', '>=', $this->request->filters['from']);
    }

    protected function to()
    {
        return $this->builder->whereDate('order_transactions.txn_date', '<=', $this->request->filters['to']);
    }

    protected function pickup_status()
    {
        if ($this->request->filters['pickup_status'] == 'picked_up') {
            return $this->builder->whereHas('signature');
        } else {
            return $this->builder->whereDoesntHave('signature');
        }
    }
}
