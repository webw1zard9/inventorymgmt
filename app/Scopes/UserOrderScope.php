<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 12/05/17
 * Time: 16:33
 */
class UserOrderScope implements Scope
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
        if (! empty(Auth::user())) {
            if (Auth::user()->isAdmin()) {
                return;
            }

            if (Auth::user()->hasRole('salesrep')) {
                $builder->where('sales_rep_id', Auth::user()->id);
            }
        }
    }
}
