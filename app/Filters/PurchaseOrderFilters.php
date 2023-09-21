<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 23:16
 */

namespace App\Filters;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderFilters extends Filters
{
    protected $filters = ['status', 'balance', 'date_preset', 'from_date', 'to_date', 'vendor', 'license_type'];

    public function __construct(Request $request)
    {
        if ($request->filled('filters.from_date') || $request->filled('filters.to_date')) {
            $request->merge($request->except('filters.date_preset'));
        }

        parent::__construct($request);

//        $this->default_filters = ['status'=>[['Unpaid'=>'Unpaid'],['Partially Paid'=>'Partially Paid']]];
        $this->default_filters = [];
        $this->cache_key = 'po_filters_'.Auth::user()->id;
    }

    protected function status()
    {
        $this->builder->whereIn('status', array_keys($this->request->filters['status']));

        return $this->builder;
    }

    protected function license_type()
    {
        return $this->builder->whereIn('customer_type', array_keys($this->request->filters['license_type']));
    }

    protected function vendor()
    {
        return $this->builder->where('vendor_id', $this->request->filters['vendor']);
    }

    protected function date_preset()
    {
        [$m, $y] = explode('-', $this->request->filters['date_preset']);

        return $this->builder->whereMonth('txn_date', $m)->whereYear('txn_date', $y);
    }

    protected function from_date()
    {
        return $this->builder->whereDate('txn_date', '>=', $this->request->filters['from_date']);
    }

    protected function to_date()
    {
        return $this->builder->whereDate('txn_date', '<=', $this->request->filters['to_date']);
    }

    protected function fund_id()
    {
        return $this->builder->where('fund_id', $this->request->filters['fund_id']);
    }

    protected function manifest_no()
    {
        return $this->builder->where('manifest_no', 'like', '%'.$this->request->filters['manifest_no'].'%');
    }

    protected function notes()
    {
        return $this->builder->where('notes', 'like', '%'.$this->request->filters['manifest_no'].'%');
    }
}
