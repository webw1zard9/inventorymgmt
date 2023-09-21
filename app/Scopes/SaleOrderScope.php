<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/13/17
 * Time: 16:33
 */
class SaleOrderScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->whereIn('orders.type', ['sale']);

        if(Auth::check()) {
            $builder->whereIn('orders.location_id', Auth::user()->only_my_locations->pluck('id'));
        }

    }
}
