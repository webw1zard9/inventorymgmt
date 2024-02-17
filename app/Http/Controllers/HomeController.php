<?php

namespace App\Http\Controllers;

use App\Batch;
use App\BatchLocation;
use App\Category;
use App\Location;
use App\PurchaseOrder;
use App\SaleOrder;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    protected $notifications;

    protected $todays_orders;

    protected $weeks_orders;

    protected $months_orders;

    protected $quarter_orders;

    protected $customers;

    protected $new_customers;

    protected $order_batch_locations;

    protected $intake_batch_locations;

    protected $order_discount_approvals;

    protected $request;

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->request = $request;

        $this->notifications = collect();

        if (resolve('inventory_intake')) {
            $this->notifications->push('You have inventory that needs to be approved. <a href="'.route('batches.intake').'">Click Here</a>');
        }

        $order_discounts = SaleOrder::where('discount_approved', 0)->count();
        $order_lines_discounts = BatchLocation::needApproval()->count();

        if ($order_discounts || $order_lines_discounts) {
            $this->notifications->push('Some orders need discount approvals. <a href="'.route('sale-orders.discount-approval').'">Click Here</a>');
        }
//        dump(Auth::user()->isAdmin());
        //dump(Auth::user()->hasRole('locationmanager'));
        if (Auth::user()->isAdmin() || Auth::user()->hasRole('locationmanager')) {
//            dump('t');
            return $this->admin_dashboard();
        }

        if (Auth::user()->hasRole('locationmanager')) {
            return $this->location_manager_dashboard();
        }

        if (Auth::user()->hasRole('salesrep')) {
            return $this->sales_rep_dashboard();
        }

        if (Auth::user()->hasRole('vendor')) {
            return $this->vendor();
        }

        if (Auth::user()->hasRole('customer')) {
            return $this->customer();
        }

        if (Auth::user()->hasRole('sauce')) {
            return $this->sauce();
        }
    }

    public function switchLocation(Location $location)
    {
        return back();
    }

    public function search()
    {
        if (! $q = request('q')) {
            return redirect(route('dashboard'));
        }

        $purchase_orders = PurchaseOrder::app_search($q)->with(['journal', 'vendor.journal'])->get();
        $sale_orders = SaleOrder::app_search($q)->with(['journal', 'customer.journal'])->get();
        $vendors = User::vendors()->appSearch($q)->with('journal')->get();
        $customers = User::customers()->with(['journal','sale_orders.journal'])->appSearch($q)->get();

        //dd($batches);
        return view('search', compact('purchase_orders', 'sale_orders', 'vendors', 'customers'));
    }

    public function batchExport()
    {
        $locations = Location::get();

        $batches = Batch::orderBy('name')
            ->with([
                'category',
                'allocated_inventory',
                'purchase_order.vendor',
                'transfer_logs_reconcile',
                'allocated_and_sold_inventory',
            ])
            ->whereIn('category_id', [19, 3, 7])
//            ->limit(10)
            ->get();

        $batch_export = collect();

        foreach ($batches as $batch) {
            $batch_item = collect([
                'Vendor' => $batch->purchase_order->vendor->name,
                'PO#' => $batch->purchase_order->ref_number,
                'PO Date' => $batch->purchase_order->txn_date->format('m/d/Y'),
                'Batch ID' => $batch->id,
                'Category Name' => $batch->category->name,
                'Batch Name' => $batch->name,
                'SKU' => $batch->ref_number,
                'Unit Cost' => $batch->unit_price,
                'Purchased Qty' => $batch->units_purchased,
                'UOM' => $batch->uom,
                'Total Qty Remaining' => $batch->inventory,
                'Reconciled Qty' => $batch->transfer_logs_reconcile->sum('quantity_transferred'),
            ]);

            foreach ($locations as $location) {
                $allocations = $batch->allocated_inventory->groupBy('id');
                $remaining = $batch->allocated_and_sold_inventory->groupBy('id');

                $allo_qty = (! empty($allocations[$location->id]) ? $allocations[$location->id]->sum('batch_location.quantity') : 0);
                $batch_item->put($location->name.' Total Allocation', $allo_qty);

                $remain_qty = (! empty($remaining[$location->id]) ? $remaining[$location->id]->sum('batch_location.quantity') : 0);
                $batch_item->put($location->name.' Remaining', $remain_qty);
            }

            $batch_export->push($batch_item);
//            if($batch->id == 147) {
//                dd($batch_export->last());
//            }
        }

        $batch_export = $batch_export->sortBy('PO Date');

        $filename = 'batches.csv';

        $columns = $batch_export->first()->keys();

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($batch_export, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns->toArray());

            foreach ($batch_export as $batch_exp) {
                fputcsv($file, $batch_exp->toArray());
            }

            fclose($file);
        };
        //dd($callback);
        return response()->stream($callback, 200, $headers);
    }

    protected function admin_dashboard()
    {
        $date_presets = date_presets();

        if ($this->request->session()->has('dashboard_date_range') && ! $this->request->has('from')) {
            $date_preset = $this->request->session()->get('dashboard_date_preset');
            $date_range = $this->request->session()->get('dashboard_date_range');
            $from = $date_range[0];
            $to = $date_range[1];
        } else {
            $date_preset = $this->request->get('preset');
            $from = Carbon::parse(($this->request->has('from') ? $this->request->get('from') : Carbon::now()->startOfMonth()))->format('Y-m-d');
            $to = Carbon::parse(($this->request->has('to') ? $this->request->get('to') : Carbon::now()->lastOfMonth()))->format('Y-m-d');
            $date_range = [$from, $to];

            $this->request->session()->put('dashboard_date_range', $date_range);
            $this->request->session()->put('dashboard_date_preset', $date_preset);
        }

        $view = view('index');

        $view->with('date_presets', $date_presets)
            ->with('date_preset', $date_preset)
            ->with('from', $from)
            ->with('to', $to);

        if (Auth::user()->hasPermission('dashboard.inventory_location')) {
//            $category_location_inventory = Batch::InventoryByCategoryLocation()->get();
            $category_location_inventory = Batch::currentInventory(null, ['category'])->get();

            $category_price_ranges = (new Category())->getPriceRanges()->groupBy('location_name');

            $view->with('category_location_inventory', $category_location_inventory);
            $view->with('category_price_ranges', $category_price_ranges);
        }

        if (Auth::user()->hasPermission('dashboard.inventory_vendor')) {
            $vendor_location_inventory = Batch::InventoryByVendorLocation()->get();
            $view->with('vendor_location_inventory', $vendor_location_inventory);
        }

        if (Auth::user()->hasPermission('dashboard.revenue_summary')) {
            $sales_by_location = (new SaleOrder())->sales_by_location($date_range);
            $view->with('sales_by_location', $sales_by_location);
        }

        if (Auth::user()->hasPermission('dashboard.revenue_by_category')) {
            $category_sales = (new Category())->revenue($date_range);
            $view->with('category_sales', $category_sales);
        }

        if (Auth::user()->hasPermission('dashboard.top_products_by_category')) {
            $top_products_by_category = (new Category())->topProducts($date_range);
            $view->with('top_products_by_category', $top_products_by_category);
        }

        if (Auth::user()->hasPermission('dashboard.sales_rep_revenue_by_category')) {
            $sales_rep_orders_by_category_with_revenue = (new SaleOrder())->sales_by_location_sales_rep($date_range);
            $view->with('sales_rep_orders_by_category_with_revenue', $sales_rep_orders_by_category_with_revenue);
        }

        $view->with('warnings', $this->notifications);

        return $view;
    }

    protected function manager()
    {
        return view('dashboard.manager');
    }

    protected function buyer()
    {
        return view('dashboard.buyer');
    }

    protected function location_manager_dashboard()
    {
        return view('index')->with('warnings', $this->notifications);
    }

    protected function sales_rep_dashboard()
    {
        $todays_orders = (new SaleOrder())->todaysOrders();

        $weeks_orders = (new SaleOrder())->weeksOrders();

        $months_orders = (new SaleOrder())->monthsOrders();

//        $quarter_orders = (new SaleOrder())->quartersOrders();

        return view('dashboard.sales-rep')->with([
            'todays_orders' => $todays_orders,
            'weeks_orders' => $weeks_orders,
            'months_orders' => $months_orders,
        ]);
    }

    protected function sauce()
    {
        return view('dashboard.sauce');
    }

    protected function vendor()
    {
        return view('dashboard.vendor');
    }

    protected function customer()
    {
        return view('dashboard.customer');
    }
}
