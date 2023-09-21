<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Category::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function canTransferTo()
    {
        return in_array($this->id, [1, 4, 6, 7, 11, 17, 18, 19, 20]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1)->orderBy('name');
    }

    public function revenue($dates)
    {
        $subquery = (new SaleOrder())->categoryRevenueByDate($dates);

        return static::select(
            'categories.*',
            'category_revenue.revenue'
            )
            ->joinSub($subquery, 'category_revenue', function ($join) {
                $join->on('categories.id', '=', 'category_revenue.id');
            })
            ->orderBy('categories.name', 'asc')
            ->get();
    }

    public function topProducts($dates)
    {
        $subquery = (new SaleOrder())->topProductsByCategory($dates);

        return static::select(
            'categories.*',
            'products.batch_id',
            'products.batch_og_name',
            'products.sold_as_name',
            'products.count',
            'products.avg_price',
            'products.sales',
            'products.vendor_name'
        )
            ->joinSub($subquery, 'products', function ($join) {
                $join->on('categories.id', '=', 'products.id');
            })
            ->orderBy('categories.name', 'asc')
            ->orderBy('products.count', 'desc')
            ->get();
    }
}
