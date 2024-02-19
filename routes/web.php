<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BatchesController;
use App\Http\Controllers\BatchLocationAggregateController;
use App\Http\Controllers\BatchLocationController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryPriceRangeController;
use App\Http\Controllers\CoingateController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\LocationManagersController;
use App\Http\Controllers\OrderDetailsController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrdersController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleOrdersController;
use App\Http\Controllers\SalesrepsController;
use App\Http\Controllers\SauceController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VendorsController;
use App\Http\Middleware\IsSuperAdmin;
use Illuminate\Support\Facades\Route;

Auth::routes();

Route::middleware('auth', 'web')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('dashboard');

    Route::get('/batch_export', [HomeController::class, 'batchExport'])->name('batch_export');

    Route::get('/coingate/rates/{from}/{to}', [CoingateController::class, 'rates'])->name('coingate-rates');

    Route::get('/switch-location/{location?}', [HomeController::class, 'switchLocation'])->name('switch-location');

    Route::get('/search', [HomeController::class, 'search'])->name('search');

    Route::get('/home', function () {
        return redirect('/');
    });
    Route::get('/logout', function () {
        return redirect('/');
    });

    Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');

    Route::get('/dashboard/revenue/{time?}', [DashboardController::class, 'revenue'])->name('dashboard.activity');

//    Route::get('/users/{type?}', 'UsersController@index')->defaults('type','all')->name('users.list');


    Route::get('/users/locationmanagers', [LocationManagersController::class, 'index'])->name('locationmanagers.index');
    Route::get('/users/vendors', [VendorsController::class, 'index'])->name('vendors.index');
    Route::get('/users/vendors/{user}', [VendorsController::class, 'show'])->name('vendors.show');
//
    Route::get('/users/vendors/{vendor}/activity-log', [VendorsController::class, 'activityLog'])->name('vendors.activity-log');
    Route::get('/users/vendors/{vendor}/statement', [VendorsController::class, 'statement'])->name('vendors.statement');

    Route::get('/users/vendors/{vendor}/payments/{purchase_order?}', [VendorsController::class, 'payment'])->name('vendors.payments');

    Route::delete('/users/vendors/{vendor}/payments/{order_transaction}', [VendorsController::class, 'paymentDestroy'])->name('vendors.payments.destroy');

    Route::get('/users/vendors/{vendor}/transactions/{order_transaction}/signature', [VendorsController::class, 'transactionsPaidSignature'])->name('vendors.transactions.paid-signature');
    Route::post('/users/vendors/{vendor}/transactions/{order_transaction}/signature', [VendorsController::class, 'transactionsPaidSignatureStore'])->name('vendors.transactions.paid-signature.store');

    Route::post('/users/vendors/{vendor}/payments', [VendorsController::class, 'storePayment'])->name('vendors.payments.store');
    Route::post('/users/vendors/{vendor}/credits', [VendorsController::class, 'storeCredit'])->name('vendors.credits.store');

    Route::get('/users/customers', [CustomersController::class, 'index'])->name('customers.index');
    Route::get('/users/salesreps', [SalesrepsController::class, 'index'])->name('salesreps.index');
    Route::get('/users/sauces', [SauceController::class, 'index'])->name('sauces.index');

    Route::get('/users/{user}/licenses/create', [LicenseController::class, 'create'])->name('users.licenses.create');
    Route::post('/users/{user}/licenses', [LicenseController::class, 'store'])->name('users.licenses.store');

    Route::post('/users/{user}/payment', [CustomersController::class, 'payment'])->name('customers.payment');

    Route::get('/users/{user}/licenses/{license}/edit', [LicenseController::class, 'edit'])->name('users.licenses.edit');
    Route::put('/users/{user}/licenses/{license}', [LicenseController::class, 'update'])->name('users.licenses.update');

    Route::get('/users/{user}/forceLogin', [UsersController::class, 'forceLogin'])->name('users.force-login');

    Route::resource('users', UsersController::class);

    Route::prefix('settings')->group(function () {
        Route::resource('locations', LocationController::class);
        Route::put('/locations/{location}/restore', [LocationController::class, 'restore'])->name('locations.restore');

        Route::resource('brands', BrandController::class);
        Route::resource('categories', CategoryController::class);

        Route::resource('categories.category-price-ranges', CategoryPriceRangeController::class)->scoped();

        Route::resource('roles', RoleController::class);

        Route::resource('permissions', PermissionController::class)->middleware(IsSuperAdmin::class);
    });

    Route::resource('batch-location', BatchLocationController::class);
    Route::resource('batch-location-aggregate', BatchLocationAggregateController::class);

    Route::middleware('level:60')->group(function () {
        Route::post('/batch-location/approve-all-intake', [BatchLocationController::class, 'approveAllIntake'])->name('batch-location.approve-all-intake');
        Route::post('/batch-location/reject-all-intake', [BatchLocationController::class, 'rejectAllIntake'])->name('batch-location.reject-all-intake');

        Route::put('/batch-location/{batch_location}/approve-discount', [BatchLocationController::class, 'approveDiscount'])->name('batch-location.approve-discount');
        Route::put('/batch-location/{batch_location}/reject-discount', [BatchLocationController::class, 'rejectDiscount'])->name('batch-location.reject-discount');
    });

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/purchase-orders', [PurchaseOrdersController::class, 'index'])->name('purchase-orders.index');
    Route::get('/purchase-orders/reset-filters', [PurchaseOrdersController::class, 'resetFilters'])->name('purchase-orders.reset-filters');

    Route::get('/purchase-orders/create/{vendor?}', [PurchaseOrdersController::class, 'create'])->name('purchase-orders.create');
    Route::get('/purchase-orders/upload/{vendor?}', [PurchaseOrdersController::class, 'create'])->name('purchase-orders.upload');

    Route::post('/purchase-orders/process-upload', [PurchaseOrdersController::class, 'processUpload'])->name('purchase-orders.process-upload');

    Route::get('/purchase-orders/{purchase_order}', [PurchaseOrdersController::class, 'show'])->name('purchase-orders.show');
    Route::post('/purchase-orders/{purchase_order}', [PurchaseOrdersController::class, 'show'])->name('purchase-orders.show-post');

    Route::get('/purchase-orders/{purchase_order}/return-items', [PurchaseOrdersController::class, 'returnItems'])->name('purchase-orders.return-items');
    Route::post('/purchase-orders/{purchase_order}/return-items', [PurchaseOrdersController::class, 'returnItemsStore'])->name('purchase-orders.return-items-store');

    Route::post('/purchase-orders/{purchase_order}/add-batch', [PurchaseOrdersController::class, 'addBatch'])->name('purchase-orders.add-batch');

    Route::get('/purchase-orders/{purchase_order}/print', [PurchaseOrdersController::class, 'printPO'])->name('purchase-orders.print_po');
    Route::get('/purchase-orders/{purchase_order}/print-qr', [PurchaseOrdersController::class, 'printQR'])->name('purchase-orders.print-qr');
    Route::post('/purchase-orders', [PurchaseOrdersController::class, 'store'])->name('purchase-orders.store');
    Route::put('/purchase-orders/{purchase_order}/update', [PurchaseOrdersController::class, 'update'])->name('purchase-orders.update');
    Route::put('/purchase-orders/{purchase_order}/update-batch/{batch}', [PurchaseOrdersController::class, 'updateBatch'])->name('purchase-orders.update-batch');
    Route::put('/purchase-orders/{purchase_order}/update-batch-inventory/{batch}', [PurchaseOrdersController::class, 'updateBatchInventory'])->name('purchase-orders.update-batch-inventory');
    Route::post('/purchase-orders/{purchase_order}/payment', [PurchaseOrdersController::class, 'payment'])->name('purchase-orders.payment');

    Route::get('/purchase-orders/{purchase_order}/retag', [PurchaseOrdersController::class, 'retag'])->name('purchase-orders.retag');
    Route::post('/purchase-orders/{purchase_order}/retag', [PurchaseOrdersController::class, 'retag'])->name('purchase-orders.retag');

    Route::post('/purchase-orders/{purchase_order}/remove', [PurchaseOrdersController::class, 'remove'])->name('purchase-orders.remove');
    Route::get('/purchase-orders/{purchase_order}/activity-log', [PurchaseOrdersController::class, 'activityLog'])->name('purchase-orders.activity-log');
    Route::post('/purchase-orders/{purchase_order}/restore', [PurchaseOrdersController::class, 'restore'])->name('purchase-orders.restore');
    Route::get('/purchase-orders/{purchase_order}/remove-all-items', [PurchaseOrdersController::class, 'removeAllItems'])->name('purchase-orders.remove-all-items');
    Route::get('/purchase-orders/{purchase_order}/allocate-items', [PurchaseOrdersController::class, 'allocateItems'])->name('purchase-orders.allocate-items');
    Route::post('/purchase-orders/{purchase_order}/allocate-items-store', [PurchaseOrdersController::class, 'allocateItemsStore'])->name('purchase-orders.allocate-items-store');

    Route::get('/sale-orders', [SaleOrdersController::class, 'index'])->name('sale-orders.index');
    Route::get('/sale-orders/reset-filters', [SaleOrdersController::class, 'resetFilters'])->name('sale-orders.reset-filters');

    Route::get('/sale-orders/export', [SaleOrdersController::class, 'export'])->name('sale-orders.export');

    Route::post('/sale-orders/store', [SaleOrdersController::class, 'store'])->name('sale-orders.store');

    Route::middleware('level:60')->group(function () {
        Route::get('/sale-orders/discount-approval', [SaleOrdersController::class, 'discountApproval'])->name('sale-orders.discount-approval');

        Route::put('/sale-orders/{sale_order}/approve-discount', [SaleOrdersController::class, 'approveDiscount'])->name('sale-orders.approve-discount');
        Route::put('/sale-orders/{sale_order}/reject-discount', [SaleOrdersController::class, 'rejectDiscount'])->name('sale-orders.reject-discount');
    });

    Route::get('/sale-orders/{sale_order}', [SaleOrdersController::class, 'show'])->name('sale-orders.show');
    Route::get('/sale-orders/{sale_order}/retag-uids', [SaleOrdersController::class, 'retagUids'])->name('sale-orders.retag-uids');
    Route::post('/sale-orders/{sale_order}/retag-uids-process', [SaleOrdersController::class, 'retagUidsProcess'])->name('sale-orders.retag-uids-process');
//    Route::get('/sale-orders/{sale_order}/retag-uids-summary', 'SaleOrdersController@retagUidsSummary')->name('sale-orders.retag-uids-summary');

    Route::get('/sale-orders/{sale_order}/uid-export', [SaleOrdersController::class, 'uidExport'])->name('sale-orders.uid-export');

    Route::put('/sale-orders/{sale_order}', [SaleOrdersController::class, 'update'])->name('sale-orders.update');
    Route::put('/sale-orders/{sale_order}/apply_discount', [SaleOrdersController::class, 'applyDiscount'])->name('sale-orders.apply-discount');

    Route::get('/sale-orders/{sale_order}/refresh-balance', [SaleOrdersController::class, 'refreshBalance'])->name('sale-orders.refresh-balance');

    Route::post('/sale-orders/{sale_order}/hold', [SaleOrdersController::class, 'hold'])->name('sale-orders.hold');
    Route::post('/sale-orders/{sale_order}/open', [SaleOrdersController::class, 'open'])->name('sale-orders.open');
    Route::post('/sale-orders/{sale_order}/ready-to-pack', [SaleOrdersController::class, 'readyToPack'])->name('sale-orders.ready-to-pack');
    Route::post('/sale-orders/{sale_order}/ready-to-deliver', [SaleOrdersController::class, 'readyToDeliver'])->name('sale-orders.ready-to-deliver');
    Route::post('/sale-orders/{sale_order}/ready-for-delivery', [SaleOrdersController::class, 'readyForDelivery'])->name('sale-orders.ready-for-delivery');
    Route::post('/sale-orders/{sale_order}/in-transit', [SaleOrdersController::class, 'inTransit'])->name('sale-orders.in-transit');
    Route::post('/sale-orders/{sale_order}/close', [SaleOrdersController::class, 'close'])->name('sale-orders.close');
    Route::post('/sale-orders/{sale_order}/payment', [SaleOrdersController::class, 'payment'])->name('sale-orders.payment');
    Route::post('/sale-orders/{sale_order}/undo-payment/{order_transaction}', [SaleOrdersController::class, 'undoPayment'])->name('sale-orders.undo-payment');
    Route::get('/sale-orders/{sale_order}/invoice', [SaleOrdersController::class, 'invoice'])->name('sale-orders.invoice');
    Route::get('/sale-orders/{sale_order}/shipping-manifest', [SaleOrdersController::class, 'shippingManifest'])->name('sale-orders.shipping-manifest');
    Route::post('/sale-orders/{sale_order}/remove', [SaleOrdersController::class, 'remove'])->name('sale-orders.remove');
    Route::post('/sale-orders/{sale_order}/remove/{order_detail}', [SaleOrdersController::class, 'removeItem'])->name('sale-orders.remove-item');
    Route::post('/sale-orders/{sale_order}/remove-all-items', [SaleOrdersController::class, 'removeAllItems'])->name('sale-orders.remove-all-item');
    Route::put('/sale-orders/{sale_order}/fulfill', [SaleOrdersController::class, 'fulfillOrderDetail'])->name('sale-orders.fulfill-order-detail');
//    Route::put('/sale-orders/{sale_order}/accept', 'SaleOrdersController@acceptOrderDetail')->name('sale-orders.accept-order-detail');

//    Route::put('/sale-orders/{sale_order}/accept-all', 'SaleOrdersController@acceptAll')->name('sale-orders.accept-all');
    Route::get('/sale-orders/{sale_order}/undo-fulfillment/{order_detail}', [SaleOrdersController::class, 'undoFulfillment'])->name('sale-orders.undo-fulfillment');
    Route::put('/sale-orders/{sale_order}/deliver-order', [SaleOrdersController::class, 'deliverOrder'])->name('sale-orders.deliver-order');
    Route::post('/sale-orders/{sale_order}/restore', [SaleOrdersController::class, 'restore'])->name('sale-orders.restore');
    Route::get('/sale-orders/{sale_order}/activity-log', [SaleOrdersController::class, 'activityLog'])->name('sale-orders.activity-log');

    Route::post('/order-details', [OrderDetailsController::class, 'store'])->name('order-details.store');
    Route::put('/order-details/{order_detail}', [OrderDetailsController::class, 'update'])->name('order-details.update');
    Route::put('/order-details/{order_detail}/retag', [OrderDetailsController::class, 'retag'])->name('order-details.retag');

//    Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
//    Route::get('/products/{product}', [ProductsController::class, 'show'])->name('products.show');
//    Route::post('/products/{product}/pickup', [ProductsController::class, 'pickup'])->name('products.pickup');
//    Route::post('/products/{product}/approve-return', [ProductsController::class, 'approveReturn'])->name('products.approve-return');
//    Route::get('/products/{product}/pickup-success', [ProductsController::class, 'pickupSuccess'])->name('products.pickup-success');
//    Route::post('/products/{product}/sell-return', [ProductsController::class, 'sellReturn'])->name('products.sell_return');
//    Route::get('/products/{product}/activity', [ProductsController::class, 'activity'])->name('products.activity');

    Route::get('/batches', [BatchesController::class, 'index'])->name('batches.index');
    Route::get('/batches/create', [BatchesController::class, 'create'])->name('batches.create');
    Route::post('/batches/store', [BatchesController::class, 'store'])->name('batches.store');

    Route::middleware('level:60')->group(function () {
        Route::get('/batches/intake', [BatchesController::class, 'intake'])->name('batches.intake');
    });

    Route::get('/batches/sold', [BatchesController::class, 'sold'])->name('batches.sold');

    Route::get('/batches/reset-filters', [BatchesController::class, 'resetFilters'])->name('batches.reset-filters');
    Route::get('/batches/search', [BatchesController::class, 'search'])->name('batches.search');
    Route::get('/batches/search-all', [BatchesController::class, 'search_all'])->name('batches.search_all');

    Route::get('/batches/qr-code/{batch}', [BatchesController::class, 'qrCode'])->name('batches.qr-code');
    Route::get('/batches/qr-codes/{category}', [BatchesController::class, 'qrCodes'])->name('batches.qr-codes');
    Route::get('/batches/print-inventory/{remove_cost?}', [BatchesController::class, 'printInventory'])->name('batches.print-inventory');


    Route::post('/batches/reconcile', [BatchesController::class, 'reconcileProcess'])->name('batches.reconcile');
    Route::get('/batches/reconcile/log/reset-filters', [BatchesController::class, 'resetReconcileLogFilters'])->name('batches.reset-reconcile-log-filters');
    Route::get('/batches/reconcile/log/{batch?}', [BatchesController::class, 'reconcileLog'])->name('batches.reconcile-log');

    Route::get('/batches/reconcile/{batch?}', [BatchesController::class, 'reconcile'])->name('batches.reconcile-list');

    Route::get('/batches/location/{location?}', [BatchesController::class, 'index'])->name('batches.location');

    Route::get('/batches/{batch}/allocate/{location}', [BatchesController::class, 'allocate'])->name('batches.allocate');
    Route::post('/batches/{batch}/allocate-store/{location}', [BatchesController::class, 'allocateStore'])->name('batches.allocate-store');

    Route::get('/batches/{batch}/sales', [BatchesController::class, 'sales'])->name('batches.sales');
    Route::get('/batches/{batch}/edit', [BatchesController::class, 'edit'])->name('batches.edit');
    Route::get('/batches/{batch}/transfer', [BatchesController::class, 'transfer'])->name('batches.transfer');

    Route::get('/batches/{batch}/transfer-log', [BatchesController::class, 'transfer_log'])->name('batches.transfer-log');
    Route::post('/batches/{batch}/transfer-log/{transfer_log?}', [BatchesController::class, 'transfer_log'])->name('batches.transfer-log');

    Route::get('/batches/{batch}/labels', [BatchesController::class, 'labels'])->name('batches.labels');
    Route::post('/batches/{batch}/transfer', [BatchesController::class, 'transfer'])->name('batches.transfer');

    Route::get('/batches/{batch}/submit_for_testing', [BatchesController::class, 'submit_for_testing'])->name('batches.submit_for_testing');

    Route::post('/batches/{batch}/pickup', [BatchesController::class, 'pickup'])->name('batches.pickup');
    Route::post('/batches/{batch}/sell', [BatchesController::class, 'sell'])->name('batches.sell');
    Route::post('/batches/{batch}/release', [BatchesController::class, 'release'])->name('batches.release');
    Route::put('/batches/{batch}/update', [BatchesController::class, 'update'])->name('batches.update');

    Route::post('/batches/{batch}/submit_for_testing', [BatchesController::class, 'submit_for_testing'])->name('batches.submit_for_testing');
    Route::post('/batches/{batch}/testing_results', [BatchesController::class, 'testing_results'])->name('batches.testing_results');

    Route::get('/batches/{batch}/activity-log', [BatchesController::class, 'activityLog'])->name('batches.activity-log');

    Route::get('/batches/{batch}/customer/{user?}', [BatchesController::class, 'show'])->name('batches.show.customer');

    Route::get('/batches/{batch}/{vault_log_ref?}', [BatchesController::class, 'show'])->name('batches.show');

    Route::middleware('permission:accounting')->group(function () {

        Route::get('/accounting/payables/vendor/{vendor}', [AccountingController::class, 'payables'])->name('accounting.vendor_payables');

        Route::get('/accounting/payables/{purchase_order?}', [AccountingController::class, 'payables'])->name('accounting.payables');
        Route::get('/accounting/payables_summary', [AccountingController::class, 'payables_summary'])->name('accounting.payables_summary');

        Route::get('/accounting/daily-report/', [AccountingController::class, 'daily_close_out_report'])->name('accounting.daily-close-out-report');
        Route::get('/accounting/daily-report/export', [AccountingController::class, 'daily_close_out_report_export'])->name('accounting.daily-close-out-report-export');

        Route::get('/accounting/chart_of_accounts/', [AccountingController::class, 'chart_of_accounts'])->name('accounting.chart_of_accounts');
        Route::get('/accounting/balance_sheet/', [AccountingController::class, 'balance_sheet'])->name('accounting.balance_sheet');

        Route::get('/accounting/profit_loss/', [AccountingController::class, 'profit_loss'])->name('accounting.profit_loss');
        Route::get('/accounting/profit_loss/export', [AccountingController::class, 'profit_loss_export'])->name('accounting.profit_loss_details_export');
        Route::get('/accounting/discounts/export', [AccountingController::class, 'discounts_export'])->name('accounting.discounts_export');

        Route::get('/accounting/transactions/paid/', [AccountingController::class, 'transactionsPaid'])->name('accounting.transactions-paid');

        Route::get('/accounting/transactions/received', [AccountingController::class, 'transactionsReceived'])->name('accounting.transactions-received');

        Route::get('/accounting/receivables/', [AccountingController::class, 'receivables'])->name('accounting.receivables');
        Route::get('/accounting/receivables/aging', [AccountingController::class, 'receivables_aging'])->name('accounting.receivables_aging');
        Route::get('/accounting/inventory-loss/', [AccountingController::class, 'inventory_loss'])->name('accounting.inventory-loss');
        Route::get('/accounting/sales-rep-commissions/', [AccountingController::class, 'sales_rep_commissions'])->name('accounting.sales_rep_commissions');
        Route::post('/accounting/sales-rep-commissions/', [AccountingController::class, 'sales_rep_commissions_store'])->name('accounting.sales_rep_commissions_store');
    });

});