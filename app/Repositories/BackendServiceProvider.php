<?php
/**
 * Created by PhpStorm.
 * User: danschultz
 * Date: 6/28/17
 * Time: 01:39
 */

namespace App\Repositories;

use Illuminate\Support\ServiceProvider;

class BackendServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(
            \App\Repositories\Contracts\UserRepositoryInterface::class,
            \App\Repositories\DbUserRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\OrderRepositoryInterface::class,
            \App\Repositories\DbOrderRepository::class
        );

        $this->app->bind(
            'App\Repositories\Contracts\BasketRepositoryInterface',
            'App\Repositories\DbBasketRepository'
        );

        $this->app->bind(
            \App\Repositories\Contracts\PurchaseOrderRepositoryInterface::class,
            \App\Repositories\DbPurchaseOrderRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\SaleOrderRepositoryInterface::class,
            \App\Repositories\DbSaleOrderRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\ProductRepositoryInterface::class,
            \App\Repositories\DbProductRepository::class
        );

        $this->app->bind(
            \App\Repositories\Contracts\BatchRepositoryInterface::class,
            \App\Repositories\DbBatchRepository::class
        );
    }
}
