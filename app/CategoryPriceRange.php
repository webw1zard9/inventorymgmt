<?php

namespace App;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryPriceRange extends Model
{
    use HasFactory;

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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

}
