<?php

namespace App\Http\Livewire\Categories\PriceRanges;

use App\Category;
use App\CategoryPriceRange;
use App\Http\Requests\StoreCategoryPriceRangeRequest;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Item extends Component
{
    public Category $category;
    public CategoryPriceRange $category_price_range;

    protected $rules = [
        'category_price_range.name' => 'required|min:3|max:255',
        'category_price_range.min_price' => 'nullable|numeric|min:0',
        'category_price_range.max_price' => 'required_if:category_price_range.min_price,null|nullable|numeric|min:0'
    ];

    protected $messages = [
        'category_price_range.name.required' => 'Name is required',
        'category_price_range.name.min' => 'Name must be at least 3 characters',
        'category_price_range.name.max' => 'Name must not be longer than 255 characters',
        'category_price_range.min_price.required' => 'Min price is required',
        'category_price_range.min_price.numeric' => 'Min price must be a number',
        'category_price_range.min_price.min' => 'Min price must be at least 0',
        'category_price_range.max_price.required' => 'Max price is required',
        'category_price_range.max_price.numeric' => 'Max price must be a number',
        'category_price_range.max_price.min' => 'Max price must be at least 0',
    ];


    public function mount(Category $category, CategoryPriceRange $category_price_range)
    {
        $this->category = $category;
        $this->category_price_range = $category_price_range;
    }

    public function render()
    {
//dump($this->category_price_range);
        return view('livewire.categories.price-ranges.item');
    }


    public function update()
    {
        $this->validate();

        if($this->category_price_range->exists) {
            $this->category_price_range->save();
            flash()->success('Payment received');
            $this->dispatch('itemUpdated');
        } else {
            $this->category->price_ranges()->save($this->category_price_range);
            $this->category_price_range = new CategoryPriceRange();
            flash()->success('Payment received');
            $this->dispatch('itemAdded');
        }

    }

    public function removeItem()
    {
        try {

            $this->category_price_range->delete();

            $this->dispatch('itemRemoved');

        } catch (\Exception $e) {

            $this->addError('od-error', $e->getMessage());
        }
    }
}
