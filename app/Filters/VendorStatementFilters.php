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

class VendorStatementFilters extends Filters
{
    protected $default_filters = [];

    protected $filters = [
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
        $this->cache_key = 'vendor_statement_filters_'.Auth::user()->id;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function from()
    {
        return $this->builder->whereDate('created_at', '>=', $this->request->filters['from']);
    }

    protected function to()
    {
        return $this->builder->whereDate('created_at', '<=', $this->request->filters['to']);
    }

}
