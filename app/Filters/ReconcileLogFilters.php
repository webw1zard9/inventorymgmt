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
use Illuminate\Support\Str;

class ReconcileLogFilters extends Filters
{
    protected $default_filters = [];

    protected $filters = ['category', 'vendor', 'from_date', 'to_date'];

    /**
     * BatchFilters constructor.
     *
     * @param  string  $cache_key
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->default_filters = [
            'date_preset' => "Today"
        ];

        $this->cache_key = 'reconcile_log_filters_'.Auth::user()->id;
    }

    public function apply($builder)
    {
        $this->builder = parent::apply($builder);

        $this->builder
            ->addSelect('batches.name as original_batch_name')
            ->addSelect('batch_location_aggregate.location_batch_name as batch_name')
            ->join('batches', 'transfer_logs.batch_id', '=', 'batches.id')
            ->join('orders', 'batches.purchase_order_id', '=', 'orders.id')
            ->leftJoin('batch_location_aggregate', function($join) {
                $join->on('transfer_logs.batch_id', '=', 'batch_location_aggregate.batch_id')
                    ->on('transfer_logs.location_id', '=', 'batch_location_aggregate.location_id');
            });
//dd($this->request->filters);
        return $this->builder;
    }

    protected function date_preset()
    {

        if($this->request->filters['date_preset'] == 'all') {
            $filters = $this->request->filters;
            unset($filters['from_date']);
            unset($filters['to_date']);
            $this->request->merge(['filters' => $filters]);
            return;
        }

        if(Str::lower($this->request->filters['date_preset']) == 'custom') {

            $from = $this->request->filters['from_date'];
            $to = $this->request->filters['to_date'];

        } else {
            $date_presets = date_presets();

            $from = $date_presets[$this->request->filters['date_preset']]['from'];
            $to = $date_presets[$this->request->filters['date_preset']]['to'];

            $filters = $this->request->filters;
            $filters['from_date'] = $from;
            $filters['to_date'] = $to;

            $this->request->merge(['filters' => $filters]);
        }

        return $this->builder->whereDate('transfer_logs.created_at', '>=', $from)->whereDate('transfer_logs.created_at', '<=', $to);
    }

    protected function name()
    {
        return $this->builder->where(function ($q) {
            $q->where('batches.name', 'like', '%'.$this->request->filters['name'].'%')
                ->orWhere('batch_location_aggregate.location_batch_name', 'like', '%'.$this->request->filters['name'].'%');
        });
    }

    protected function location_id()
    {
        if($this->request->filters['location_id'] == 0) { //nest
            return $this->builder->whereNull('transfer_logs.location_id');
        } else {
            return $this->builder->where('transfer_logs.location_id', $this->request->filters['location_id']);
        }
    }

    protected function ref_number()
    {
        return $this->builder->where('batches.ref_number', 'like', '%'.$this->request->filters['ref_number'].'%');
    }

    protected function category_id()
    {
        return $this->builder->whereIn('batches.category_id', array_keys($this->request->filters['category_id']));
    }

    protected function vendor_id()
    {
        return $this->builder->where('orders.vendor_id', $this->request->filters['vendor_id']);
    }

    protected function brand_id()
    {
        return $this->builder->where('batches.brand_id', $this->request->filters['brand_id']);
    }

}
