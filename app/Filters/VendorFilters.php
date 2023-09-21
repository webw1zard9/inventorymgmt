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

class VendorFilters extends Filters
{
    protected $default_filters = [];

    protected $filters = [
        'vendor',
    ];

    /**
     * BatchFilters constructor.
     *
     * @param  string  $cache_key
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->cache_key = 'vendor_filters_'.Auth::user()->id;
    }

    /**
     * @param $builder
     * @return mixed
     */

    protected function vendor()
    {
        return $this->builder->where('id', $this->request->filters['vendor']);
    }

}
