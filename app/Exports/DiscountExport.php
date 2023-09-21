<?php

namespace App\Exports;

use App\SaleOrder;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class DiscountExport implements FromView
{
    public function __construct($sale_orders)
    {
        $this->sale_orders = $sale_orders;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view('exports.discounts')->with('sale_orders', $this->sale_orders);
    }
}
