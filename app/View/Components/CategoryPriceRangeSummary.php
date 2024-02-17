<?php

namespace App\View\Components;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class CategoryPriceRangeSummary extends Component
{
    public $price_ranges;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($price_ranges)
    {
        $this->price_ranges = $price_ranges;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        dd($his->price_ranges);
        return view('components.category-price-range-summary');
    }
}
