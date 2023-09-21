<?php

namespace App\Providers;

use App\Http\ViewComposers\Accounting\PayablesComposer;
use App\Http\ViewComposers\Accounting\PayablesSummaryComposer;
use App\Http\ViewComposers\Accounting\ReceivablesAgingComposer;
use App\Http\ViewComposers\Accounting\SalesRepCommissionsComposer;
use App\Http\ViewComposers\Batches\IndexComposer as BatchIndexComposer;
use App\Http\ViewComposers\Batches\ShowComposer as BatchShowComposer;
use App\Http\ViewComposers\Batches\TransferLogComposer as BatchTransferLogComposer;
use App\Http\ViewComposers\Exports\SaleOrdersComposer;
use App\Http\ViewComposers\Home\IndexComposer;
use App\Http\ViewComposers\PurchaseOrders\IndexComposer as POIndexComposer;
use App\Http\ViewComposers\PurchaseOrders\ReTagComposer;
use App\Http\ViewComposers\PurchaseOrders\ReturnItemsComposer;
use App\Http\ViewComposers\PurchaseOrders\ReviewUploadComposer;
use App\Http\ViewComposers\PurchaseOrders\ShowComposer as POShowComposer;
use App\Http\ViewComposers\SaleOrders\DiscountApprovalComposer;
use App\Http\ViewComposers\SaleOrders\IndexComposer as SOIndexComposer;
use App\Http\ViewComposers\SaleOrders\InvoiceComposer as SOInvoiceComposer;
use App\Http\ViewComposers\SaleOrders\RetagUidsComposer as SORetagUidComposer;
use App\Http\ViewComposers\SaleOrders\ShippingManifestComposer as SOShipManifestComposer;
use App\Http\ViewComposers\SaleOrders\ShowComposer as SOShowComposer;
use App\Http\ViewComposers\Users\ShowComposer as UserShowComposer;
use App\Http\ViewComposers\Users\Vendors\ShowComposer as VendorShowComposer;
use App\Http\ViewComposers\Users\Vendors\VendorStatementComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('index', IndexComposer::class);
        View::composer('batches.index', BatchIndexComposer::class);
        View::composer('batches.show', BatchShowComposer::class);
        View::composer('batches.transfer-log', BatchTransferLogComposer::class);

        View::composer('purchase_orders.index', POIndexComposer::class);
        View::composer('purchase_orders.show', POShowComposer::class);
        View::composer('purchase_orders.review-upload', ReviewUploadComposer::class);
        View::composer('purchase_orders.retag', ReTagComposer::class);
        View::composer('purchase_orders.return_items', ReturnItemsComposer::class);

        View::composer('sale_orders.index', SOIndexComposer::class);
        View::composer('sale_orders.show', SOShowComposer::class);
        View::composer('sale_orders.invoice', SOInvoiceComposer::class);
        View::composer('sale_orders.discount_approval', DiscountApprovalComposer::class);

        View::composer('batches.print-inventory', BatchIndexComposer::class);
        View::composer('accounting.receivables_aging', ReceivablesAgingComposer::class);
        View::composer('accounting.payables', PayablesComposer::class);
        View::composer('accounting.payables_summary', PayablesSummaryComposer::class);
        View::composer('accounting.sales_rep_commissions', SalesRepCommissionsComposer::class);

        View::composer('exports.sale-orders', SaleOrdersComposer::class);

        View::composer('users.show', UserShowComposer::class);
        View::composer('users.vendors.show', VendorShowComposer::class);
        View::composer('users.vendors.statement', VendorStatementComposer::class);
        View::composer('users.vendors.statement-pdf', VendorStatementComposer::class);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
