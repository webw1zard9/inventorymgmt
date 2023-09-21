<?php

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 11/30/17
 * Time: 21:54
 */

namespace App\Http\ViewComposers\Batches;

use App\Category;
use Illuminate\View\View;

class IndexComposer
{
    public function compose(View $view)
    {
        $categories = Category::all();
        $cat_map = $categories->pluck('name', 'id')->toArray();

        $filtered_inventory_value = 0;

        foreach ($view->batches->groupBy(['category_id']) as $category_id => $batches) {
            $collection = collect([
                'name' => $cat_map[$category_id],
                'inventory' => $batches->sum('inventory'),
                'batches' => $batches->sortBy('parent_batch_name')->groupBy(['brand_name']),
            ]);

            $collection->put('inventory_value', $batches->sum('batch_value'));

            $filtered_inventory_value += $collection['inventory_value'];
        }

        $view
            ->with('filtered_inventory_value', $filtered_inventory_value);
    }
}
