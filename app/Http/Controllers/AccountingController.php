<?php

namespace App\Http\Controllers;

use App\ChartOfAccount;
use App\Customer;
use App\Exports\DiscountExport;
use App\Exports\RevenueExport;
use App\Filters\TransactionPaidFilters;
use App\Filters\VendorFilters;
use App\Location;
use App\Order;
use App\OrderTransaction;
use App\OrderTransactionSignatures;
use App\PurchaseOrder;
use App\Repositories\DbUserRepository;
use App\SaleOrder;
use App\SalesCommission;
use App\SalesCommissionDetail;
use App\TransferLog;
use App\User;
use App\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Scottlaurent\Accounting\Models\Ledger;

class AccountingController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        view()->share('title', 'Accounting');
    }

    public function balance_sheet()
    {
        view()->share('title', 'Balance Sheet');

        $date = Carbon::now()->subDays(0);

//        dump($assets);

        $ledgers = Ledger::with(['journal_transactions' => function ($q) use ($date) {
            $q->where('post_date', '<=', $date);
        }])->get();

        $ledger_by_gruops = $ledgers->groupBy('type');

//        dump($ledger_gruops['assets']->getCurrentBalanceInDollars());

        $total_assets = 0;
        foreach ($ledger_by_gruops['asset'] as $asset_ledger) {
//            dump($asset_ledger->name);
//            dump($asset_ledger->getCurrentBalanceInDollars());
            $total_assets += $asset_ledger->getCurrentBalanceInDollars();
        }
//        dump("--------");

        $total_liabilities = 0;
        foreach ($ledger_by_gruops['liability'] as $liability_ledger) {
//            dump($liability_ledger->name);
//            dump($liability_ledger->getCurrentBalanceInDollars());
            $total_liabilities += $liability_ledger->getCurrentBalanceInDollars();
        }
//        dump("--------");
//        dump("Total Liabilities:");
//        dump(display_currency($total_liabilities));
//

        $total_rev = 0;
        foreach ($ledger_by_gruops['income'] as $rev_ledger) {
//            dump($liability_ledger->name);
//            dump($liability_ledger->getCurrentBalanceInDollars());
            $total_rev += $rev_ledger->getCurrentBalanceInDollars();
        }
//        dump("--------");
//        dump("Total Revenue:");
//        dump(display_currency($total_rev));

        $total_exp = 0;
        foreach ($ledger_by_gruops['expense'] as $exp_ledger) {
//            dump($liability_ledger->name);
//            dump($liability_ledger->getCurrentBalanceInDollars());
            $total_exp += $exp_ledger->getCurrentBalanceInDollars();
        }
//        dump("--------");
//        dump("Total Expense:");
//        dump(display_currency($total_exp));

        $total_equity = 0;
        foreach ($ledger_by_gruops['equity'] as $equity_ledger) {
//            dump($liability_ledger->name);
//            dump($liability_ledger->getCurrentBalanceInDollars());
            $total_equity += $equity_ledger->getCurrentBalanceInDollars();
        }

        $retained_earnings = $total_rev - $total_exp;

//        dump("--------");
//        dump("Total Equity:");
//        dump(display_currency($total_equity));

//        dump("Total assets:");
//        dump(display_currency($total_assets));
//
//        dump("------------------------------------");
//
//        dump("Total Liabilities:");
//        dump(display_currency($total_liabilities));
//
//        dump("Total Equity:");
//        dump(display_currency($total_equity + $total_rev - $total_exp));
//
//        dump("Total Liab/Equity");
//        dump(display_currency($total_liabilities + ($total_equity + $total_rev - $total_exp)));
//
//
//        dd("e");

        return view('accounting.balance_sheet', compact(
            'ledger_by_gruops',
            'total_assets',
            'total_liabilities',
            'total_equity', 'retained_earnings'));
    }

    public function profit_loss(Request $request)
    {
        if (Gate::denies('accounting.profitloss')) {
            flash()->error('Permission Denied!');

            return redirect(route('dashboard'));
        }

        view()->share('title', 'Profit & Loss');

        $date_presets = date_presets();

        if ($request->session()->has('accounting_date_range') && ! $request->has('from')) {
            $date_preset = $request->session()->get('accounting_date_preset');
            $date_range = $request->session()->get('accounting_date_range');
            $from = $date_range[0];
            $to = $date_range[1];
        } else {
            $date_preset = $request->get('preset');
            $from = Carbon::parse(($request->has('from') ? $request->get('from') : Carbon::now()))->format('Y-m-d');
            $to = Carbon::parse(($request->has('to') ? $request->get('to') : Carbon::now()))->format('Y-m-d');
            $date_range = [$from, $to];

            $request->session()->put('accounting_date_range', $date_range);
            $request->session()->put('accounting_date_preset', $date_preset);
        }

        $locations = Location::profit_and_loss([$from, $to]);

        $nest_reconciliations = TransferLog::reconciliations([$from, $to]);

        $reconciliation_filters = collect([
            'name'=>'',
            'ref_number'=>'',
            'brand_id'=>'',
            'vendor_id'=>'',
            'category_id'=>'',
            'date_preset'=>$date_preset,
            'from_date'=>$from,
            'to_date'=>$to
        ]);

//dd($reconciliation_filters);

//        dd($reconciliation_filters->merge(['location_id'=>1])->toArray());
        return view('accounting.profit_loss', compact(
            'date_presets',
            'date_preset',
            'from',
            'to',
            'locations',
            'nest_reconciliations',
            'reconciliation_filters'
        ));
    }

    public function profit_loss_export(Request $request)
    {
        if (Gate::denies('accounting.profitloss')) {
            flash()->error('Permission Denied!');
            return redirect(route('dashboard'));
        }

        if ($request->session()->has('accounting_date_range') && ! $request->has('from')) {
            $date_range = $request->session()->get('accounting_date_range');
            $from = $date_range[0];
            $to = $date_range[1];
        }

        try {
            $locations = Location::profit_and_loss([$from, $to]);

            $store_name = (Auth::check() && Auth::user()->hasLocation() ? Auth::user()->current_location->name : 'NO');
            $filename_parts = collect([
                $store_name,
                'Revenue-Details',
                $from,
                $to,
            ]);

            $filename = $filename_parts->implode('-').'.xlsx';

            return Excel::download(new RevenueExport($locations), $filename);
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
            return redirect(route('accounting.profit_loss'));
        }
    }

    public function discounts_export(Request $request)
    {
        if (Gate::denies('accounting.profitloss')) {
            flash()->error('Permission Denied!');
            return redirect(route('dashboard'));
        }

        try {

            if ($request->session()->has('accounting_date_range') && ! $request->has('from')) {
                $date_range = $request->session()->get('accounting_date_range');
                $from = $date_range[0];
                $to = $date_range[1];
            }

            $location_id = $request->get('location_id');

            $sale_orders = SaleOrder::withDiscountDetails([$from, $to], $location_id)->get();

            $store_name = (Auth::check() && Auth::user()->hasLocation() ? Auth::user()->current_location->name : 'NO');
            $filename_parts = collect([
                $store_name,
                'Order-Discounts',
                $from,
                $to,
            ]);

            $filename = $filename_parts->implode('-').'.xlsx';

            return Excel::download(new DiscountExport($sale_orders), $filename);

        } catch(\Exception $e) {
            flash()->error($e->getMessage());
            return redirect(route('accounting.profit_loss'));
        }



    }

    public function chart_of_accounts()
    {
        view()->share('title', 'Chart of Accounts');

        $date = Carbon::now();

        $ledgers = Ledger::with(['journals', 'journal_transactions' => function ($q) use ($date) {
            $q->where('post_date', '<=', $date);
        }])->get();

//        $chart_of_accounts = ChartOfAccount::orderBy('code')->get();

        return view('accounting.chart_of_accounts', compact('ledgers'));
    }

    public function transactionsPaid(Request $request, VendorFilters $vendorFilters, TransactionPaidFilters $transactionPaidFilters)
    {
        view()->share('title', 'Transactions Paid');

        $date_presets = date_presets();

        $filters = $transactionPaidFilters->getFilters()->toArray();

        if ($filters) {
            $from = $filters['from'];
            $to = $filters['to'];
        } else {
            $from = Carbon::parse((! empty($request->get('filters')['from']) ? $request->get('filters')['from'] : Carbon::now()))->format('Y-m-d');
            $to = Carbon::parse((! empty($request->get('filters')['to']) ? $request->get('filters')['to'] : Carbon::now()))->format('Y-m-d');
        }

        $all_vendors = User::vendors()->orderBy('name')->get();

        $vendors = User::vendors()
            ->filters($vendorFilters)
            ->paidTransactions($transactionPaidFilters)
            ->orderBy('name')
            ->get();

        $transactions_total_amount = $vendors->sum(function($vendor) {
            return $vendor->vendor_transactions->sum('amount');
        });

        $transactions_count = $vendors->sum(function($vendor) {
            return $vendor->vendor_transactions->count();
        });

        return view('accounting.transactions.paid', compact(
            'filters',
            'all_vendors',
            'date_presets',
            'from',
            'to',
            'vendors',
            'transactions_total_amount',
            'transactions_count'
        ));
    }



    public function transactionsReceived(Request $request)
    {
        view()->share('title', 'Transactions Received');

        $date_presets = date_presets();

        if ($request->session()->has('accounting_txn_rcd_date_range') && ! $request->has('from')) {
            $date_preset = $request->session()->get('accounting_txn_rcd_date_preset');
            $date_range = $request->session()->get('accounting_txn_rcd_date_range');
            $from = $date_range[0];
            $to = $date_range[1];
        } else {
            $date_preset = $request->get('preset');
            $from = Carbon::parse(($request->has('from') ? $request->get('from') : Carbon::now()))->format('Y-m-d');
            $to = Carbon::parse(($request->has('to') ? $request->get('to') : Carbon::now()))->format('Y-m-d');
            $date_range = [$from, $to];

            $request->session()->put('accounting_txn_rcd_date_range', $date_range);
            $request->session()->put('accounting_txn_rcd_date_preset', $date_preset);
        }

        $transactions = OrderTransaction::with([
            'user',
            'location',
            'sale_order.sales_rep',
            'sale_order.customer',
            'sale_order.location',
            'journal_transaction.journal.morphed',
        ])
            ->whereIn('type', ['payment', 'refund'])
            ->whereNotNull('sale_order_id')
            ->where('payment_method', '!=', 'Credit')
            ->whereDate('txn_date', '>=', $from)
            ->whereDate('txn_date', '<=', $to)
            ->get();

        return view('accounting.transactions.received', compact(
//            'selected_date_preset',
            'date_presets',
            'date_preset',
            'from',
            'to',
            'transactions'
        ));
    }

    public function payables_summary()
    {
        view()->share('title', 'Accounting / Vendor Payables');

        $vendors = User::vendors()->payablesSummary()->get();
//dd($vendors);
        $payable_data = (new User)->aggregatePayablesSummary($vendors);
//dd($payable_data);
        return view('accounting.payables_summary', compact(
            'payable_data',
        ));

    }

    public function payables(PurchaseOrder $purchaseOrder, User $vendor)
    {
        if (Gate::denies('accounting.payables')) {
            flash()->error('Permission Denied!');

            return redirect(route('dashboard'));
        }

        view()->share('title', 'Vendor Payables');

        $vendors = User::vendors()->payables($purchaseOrder, $vendor)->get();

        $show_all = ($purchaseOrder->exists || $vendor->exists) ? true : false;
//
//        dd($vendors);
        return view('accounting.payables', compact(
            'show_all',
            'vendors',
            'purchaseOrder'
        ));
    }

    public function receivables_aging()
    {
        if (Gate::denies('accounting.receivables.aging')) {
            return redirect(route('dashboard'));
        }

        view()->share('title', 'Accounting Receivables > Aging');

        $sale_orders = SaleOrder::withOutstandingBalance()->with('customer')->get();

        return view('accounting.receivables_aging', compact('sale_orders'));
    }

    public function inventory_loss()
    {
        if (Gate::denies('accounting.inventoryloss')) {
            flash()->error('Permission Denied!');

            return redirect(route('dashboard'));
        }

        view()->share('title', 'Accounting / Inventory Loss');

        $inventory_loss = TransferLog::inventoryLoss()->get();

        return view('accounting.inventory-loss', compact('inventory_loss'));
    }

    public function sales_rep_commissions()
    {
        view()->share('title', 'Accounting / Sales Rep Commissions');

        $sales_rep = null;
        $sale_orders = null;
        $start_date = null;
        $end_date = null;
        $commission_rate = 0;
        $sales_commissions = null;

        $sales_reps = User::salesReps()->where('active', 1)->orderBy('name')->get()->pluck('name', 'id');

        $sales_commission = null;
        if ($sales_commission_id = request('sales_commission_id')) {
            $sales_commission = SalesCommission::where('id', $sales_commission_id)->with(['user', 'sales_rep', 'sales_commission_details.sale_order.customer'])->first();
        }

        if ($sales_rep_id = request('sales_rep_id')) {
            $sales_rep = User::find($sales_rep_id);

            $commission_rate = ($sales_rep->hasRole('salesmanager') ? 0.01 : 0.07);

            if (request('end_date') == '2018-09-22') {
                $end_date = Carbon::createFromFormat('Y-m-d', request('end_date'));
                $start_date = Carbon::createFromFormat('Y-m-d', '2018-09-01');
            } else {
                $end_date = Carbon::createFromFormat('Y-m-d', request('end_date'));
                $start_date = $end_date->copy()->subWeek(1)->startOfWeek();
            }

            ///get existing sales commissions
            $sales_commissions = $sales_rep->my_sales_commissions;

            $sales_commissions->transform(function ($sales_commission) {
                $sales_commission['pay_period'] = $sales_commission->period_start_formatted.' - '.$sales_commission->period_end_formatted;

                return $sales_commission;
            });

//            dd($sales_commissions);

            $sale_orders = null;
            if (! $sales_rep->hasSalesCommForPeriod($start_date, $end_date)) {
                //get orders
                $sale_orders_qry = SaleOrder::select('orders.*')
                    ->with(['customer', 'sales_rep'])
                    ->with(['sales_commission_details' => function ($qry) use ($sales_rep) {
                        $qry->where('sales_rep_id', $sales_rep->id);
                    }])
                    ->whereDoesntHave('transactions', function ($qry) use ($end_date) {
                        $qry->whereDate('txn_date', '>', $end_date->toDateString());
                    })
//                ->whereDoesntHave('sales_commissions')
                    ->with('customer.first_sale_order')
                    ->with('customer.sale_orders')
                    ->whereDate('orders.txn_date', '>', config('inventorymgmt.sales_commission_start_date'))
                    ->whereDate('orders.txn_date', '<=', $end_date->toDateString())
                    ->where(function ($qry) {
                        $qry->where('balance', '<=', 0)->orWhereIn('customer_id', ['95', '34']);
                    })
                    ->whereIn('sale_type', ['packaged', 'bulk'])
                    ->whereIn('status', ['delivered', 'returned'])
                    ->orderBy('orders.txn_date', 'desc');

                if (! $sales_rep->hasRole('salesmanager')) {
                    $sale_orders_qry->where('sales_rep_id', $sales_rep->id);
                } else {
                    $sale_orders_qry->whereNotNull('sales_rep_id');
                }

                $sale_orders = $sale_orders_qry->get();
            }
            //dd($sale_orders);
        }

        return view('accounting.sales_rep_commissions', compact('sales_reps', 'sales_rep', 'sale_orders', 'start_date', 'end_date', 'commission_rate', 'sales_commissions', 'sales_commission'));
    }

    public function sales_rep_commissions_store(Request $request)
    {
        $sale_orders = collect($request->get('sale_orders'))->transform(function ($t) use ($request) {
            $t['rate'] /= 100;
            $t['amount'] = round($t['subtotal'] * $t['rate'], 2);
            $t['sales_rep_id'] = $request->get('sales_rep_id');

            return new SalesCommissionDetail($t);
        });

        $sales_commission = Auth::user()->created_sales_commissions()->save(new SalesCommission([
            'sales_rep_id' => $request->get('sales_rep_id'),
            'period_start' => $request->get('period_start'),
            'period_end' => $request->get('period_end'),
            'total_revenue' => $request->get('total_revenue'),
            'total_commission' => $sale_orders->sum('amount'),
        ]));

        $sales_commission->sales_commission_details()->saveMany($sale_orders);

        flash()->success('Sales Commissions Saved!');

        return back()->withInput();
    }
}
