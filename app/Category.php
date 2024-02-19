<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    protected $guarded = [];

    protected function minPrice(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? $value / 100 : null,
            set: fn($value) => $value ? $value * 100 : 0,
        );
    }

    protected function maxPrice(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? $value / 100 : null,
            set: fn($value) => $value ? $value * 100 : null,
        );
    }
    public function products()
    {
        return $this->hasMany(Category::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function price_ranges()
    {
        return $this->hasMany(CategoryPriceRange::class);
    }

    public function scopeWithMinMaxAvgBatchSalePrice(Builder $builder)
    {
        /**
         * SELECT
         * categories.id,
         * categories.name,
         * batches.uom,
         * min(bla.suggested_unit_sale_price)/100,
         * max(bla.suggested_unit_sale_price)/100,
         * sum(bla.onhand_inventory),
         * (sum(bla.suggested_unit_sale_price * bla.onhand_inventory) / sum(bla.onhand_inventory))/100
         * FROM
         * categories
         * INNER JOIN `batches` ON `categories`.`id` = `batches`.`category_id`
         * LEFT JOIN ( select * from `batch_location_aggregate` where onhand_inventory > 0) `bla` ON `bla`.`batch_id` = `batches`.`id`
         * group by categories.id, batches.uom
         * order by categories.name;
         */



        return $builder
            ->addSelect(
                'categories.*',
                'batches.uom as batch_uom',
                \DB::raw('min(bla.suggested_unit_sale_price)/100 AS batch_min_price'),
                \DB::raw('max(bla.suggested_unit_sale_price)/100 AS batch_max_price'),
                \DB::raw('(sum(bla.suggested_unit_sale_price * bla.onhand_inventory) / sum(bla.onhand_inventory))/100 AS batch_avg_price'),
                \DB::raw('sum(bla.onhand_inventory) AS batch_inventory')
            )
            ->join('batches', 'categories.id', '=', 'batches.category_id')
            ->leftJoinSub(
                BatchLocationAggregate::query()->where('onhand_inventory', '>', 0),
                'bla',
                'bla.batch_id',
                '=',
                'batches.id'
            )
            ->groupBy('categories.id', 'batches.uom');
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

    public function getPriceRanges()
    {

        return static::query()
            ->select(
                'categories.id',
                'categories.name AS category_name',
                'category_price_ranges.name AS price_range_name',
                'locations.name AS location_name',
                'category_price_ranges.min_price',
                'category_price_ranges.max_price',
                'batches.uom',
                \DB::raw('COUNT(bla.batch_id) AS batches_count'),
                \DB::raw('SUM(bla.onhand_inventory) AS inventory'),
                \DB::raw('SUM(bla.onhand_cost)/100 AS inv_value')
            )
            ->join('category_price_ranges', 'categories.id', '=', 'category_price_ranges.category_id')
            ->join('batches', 'categories.id', '=', 'batches.category_id')
            ->leftJoin('batch_location_aggregate as bla', function ($join) {
                $join->on('bla.batch_id', '=', 'batches.id')
                    ->where('bla.suggested_unit_sale_price', '>=', DB::raw('`category_price_ranges`.`min_price`'))
                    ->where(function ($query) {
                        $query->where('bla.suggested_unit_sale_price', '<=', DB::raw('`category_price_ranges`.`max_price`'))
                            ->orWhereNull('category_price_ranges.max_price');
                    });
            })
            ->join('locations', 'bla.location_id', '=', 'locations.id')
            ->where('bla.onhand_inventory', '>', 0)
            ->groupBy('categories.id', 'category_price_ranges.id', 'bla.location_id', 'batches.uom')
            ->orderBy('categories.id')
            ->orderBy('category_price_ranges.min_price')
            ->get();

    }

}
