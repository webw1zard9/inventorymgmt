<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 6/7/22
 * Time: 18:11
 */

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class LocationScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check() && Auth::user()->only_my_locations->count()) {
            $builder->where(function($q) use ($model) {
                $q->whereIn($model->getTable().'.location_id', Auth::user()->only_my_locations->pluck('id'))
                ->orWhereNull($model->getTable().'.location_id');
            });

        }
    }
}
