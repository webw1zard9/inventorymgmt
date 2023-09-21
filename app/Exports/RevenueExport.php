<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class RevenueExport implements FromView
{
    public function __construct($locations)
    {
        $this->locations = $locations;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view('exports.revenue-details')->with('locations', $this->locations);
    }
}
