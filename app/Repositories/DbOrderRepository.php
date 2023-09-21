<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 7/7/17
 * Time: 16:13
 */

namespace App\Repositories;

use App\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\Auth;

abstract class DbOrderRepository implements OrderRepositoryInterface
{
    protected $grams_in_a_pound = 454;

    protected $order_class = Order::class;

    protected $order_type = 'purchase';

    public function all($with = ['vendor', 'customer'], $orderBy = ['txn_date', 'desc'])
    {
        $order = app($this->order_class)::with($with);
        $order->orderBy($orderBy[0], $orderBy[1]);

        if (Auth::user()->hasRole('transporter')) {
            $order->where('user_id', Auth::user()->id);
        }

        return $order->get();
    }

    public function find($id, $with = ['vendor', 'customer'])
    {
        return app($this->order_class)::with($with)->withTrashed()->find($id);
    }
}
