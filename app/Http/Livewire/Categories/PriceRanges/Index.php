<?php

namespace App\Http\Livewire\Categories\PriceRanges;

use App\Category;
use Livewire\Component;

class Index extends Component
{
    public Category $category;

    public $listeners = [
        'itemAdded' => '$refresh',
        'itemUpdated' => 'render',
        'itemRemoved' => 'render'
    ];

    public function mount(Category $category)
    {
        $this->category = $category;
    }

    public function render()
    {
        $this->category->load(['price_ranges'=> function($query) {
            $query->orderBy('min_price');
        }]);

        return view('livewire.categories.price-ranges.index');
    }
}
