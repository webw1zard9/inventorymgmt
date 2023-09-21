<?php

namespace App\Providers;

use App\Batch;
use App\Location;
use App\OrderDetail;
use App\PurchaseOrder;
use App\Repositories\DbProductRepository;
use App\Repositories\DbSaleOrderRepository;
use App\Repositories\DbUserRepository;
use App\TransferLog;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot()
    {
        parent::boot();

        Route::bind('user', function ($id) {
            return (new DbUserRepository())->find($id);
        });

        Route::bind('purchase_order', function ($id) {
            return PurchaseOrder::withTrashed()->with('user')->find($id);
        });

        Route::bind('sale_order', function ($id) {
            return (new DbSaleOrderRepository())->find($id, ['vendor', 'customer']);
        });

        Route::bind('batch', function ($id) {
            return Batch::find($id);
        });

        Route::bind('order_detail', function ($id) {
            return OrderDetail::find($id);
        });

        Route::bind('transfer_log', function ($id) {
            return TransferLog::find($id);
        });

        Route::bind('location', function ($id) {

            return Location::withTrashed()->find($id);
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
             ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->group(base_path('routes/api.php'));
    }
}
