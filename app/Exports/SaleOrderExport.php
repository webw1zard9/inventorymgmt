<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 6/10/22
 * Time: 08:57
 */

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SaleOrderExport implements FromView
{
    public function __construct($sale_orders)
    {
        $this->sale_orders = $sale_orders;
    }

    public function view(): View
    {
        return view('exports.sale-orders')->with('sale_orders', $this->sale_orders);
    }
}
