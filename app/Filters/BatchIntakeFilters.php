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

class BatchIntakeFilters extends Filters
{
    protected $default_filters = [];

    protected $filters = ['name', 'category'];

    /**
     * BatchFilters constructor.
     *
     * @param  string  $cache_key
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->cache_key = 'batch_intake_filters_'.Auth::user()->id;
    }

    protected function name()
    {
        return $this->builder->whereHas('batch', function ($query) {
            $query->where('name', 'like', '%'.$this->request->filters['name'].'%');
        });
    }

    protected function sku()
    {
        return $this->builder->whereHas('batch', function ($query) {
            $query->where('ref_number', 'like', '%'.$this->request->filters['sku'].'%');
        });
    }

    protected function category()
    {
        return $this->builder->join('batches', 'batch_location.batch_id', '=', 'batches.id')
            ->join('categories', 'batches.category_id', '=', 'categories.id')
            ->whereIn('categories.id', array_keys($this->request->filters['category']));
    }

    protected function uom()
    {
        return $this->builder->whereHas('batch', function ($query) {
            $query->whereIn('uom', array_keys($this->request->filters['uom']));
        });
    }
}
