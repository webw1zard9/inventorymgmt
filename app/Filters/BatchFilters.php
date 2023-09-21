<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 16:56
 */

namespace App\Filters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BatchFilters extends Filters
{
    protected $default_filters = [];

    protected $filters = ['status', 'name', 'category', 'vendor'];

    /**
     * BatchFilters constructor.
     *
     * @param  string  $cache_key
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->cache_key = 'batch_filters_'.Auth::user()->id;
    }

    /**
     * @param $builder
     * @return mixed
     */
    protected function status()
    {
        $statuses = collect(array_keys($this->request->filters['status']));

        if ($statuses->count()) {
            $this->builder->whereIn('batches.status', $statuses);
        }

        return $this->builder;
    }


    protected function name()
    {
        return $this->builder->where(function ($q) {
            $q->where('batches.name', 'like', '%'.$this->request->filters['name'].'%')
                ->orWhere('batch_location_aggregate.location_batch_name', 'like', '%'.$this->request->filters['name'].'%');
        });
    }

    protected function batch_id()
    {
        return $this->builder->where('batches.ref_number', 'like', '%'.$this->request->filters['batch_id'].'%');
    }

    protected function fund_id()
    {
        return $this->builder->where('fund_id', $this->request->filters['fund_id']);
    }

    protected function category()
    {
        return $this->builder->whereIn('category_id', array_keys($this->request->filters['category']));
    }

    protected function uom()
    {
        return $this->builder->whereIn('uom', array_keys($this->request->filters['uom']));
    }

    protected function vendor()
    {
        return $this->builder->join('orders', 'batches.purchase_order_id', '=', 'orders.id')
            ->where('orders.vendor_id', $this->request->filters['vendor']);
    }

    protected function brand_id()
    {
        return $this->builder->where('brand_id', $this->request->filters['brand_id']);
    }

//    protected function brand()
//    {
//        return $this->builder->whereIn('brand_id', array_keys($this->request->filters['brand']));
//    }

    protected function not_available_inventory()
    {
        return $this->builder->where('available_inventory', '=', 0);
    }

    protected function available_inventory()
    {
        return $this->builder->where('available_inventory', '>', 0);
    }

    protected function pending_inventory()
    {
        return $this->builder->where('pending_inventory', '>', 0);
    }

    protected function type()
    {
        return $this->builder->whereIn('batches.type', array_keys($this->request->filters['type']));
    }

    protected function non_inventory()
    {
        return $this->builder->where('batches.track_inventory', 0);
    }

}
