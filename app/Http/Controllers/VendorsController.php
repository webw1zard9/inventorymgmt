<?php

namespace App\Http\Controllers;

use App\Batch;
use App\BatchLocationAggregate;
use App\ChartOfAccount;
use App\Filters\VendorStatementFilters;
use App\Location;
use App\OrderTransaction;
use App\OrderTransactionSignatures;
use App\PurchaseOrder;
use App\SaleOrder;
use App\User;
use App\Vendor;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Scottlaurent\Accounting\Models\JournalTransaction;
use Scottlaurent\Accounting\Services\Accounting;

class VendorsController extends Controller
{
    public function index()
    {
        if (Gate::denies('users.index.vendor')) {
            flash('Access Denied!')->error();

            return back();
        }

        view()->share('title', 'Vendors');

        $vendors = User::vendors()
            ->with(['purchase_orders.journal','journal.ledger','roles'])
            ->orderBy('name')
            ->get();

        $role = 'Vendor';

        return view('users.vendors.index', compact('vendors', 'role'));
    }

    public function show(User $vendor)
    {
        if (Gate::denies('users.view') && Gate::denies('users.view.vendor')) {
            flash('Access Denied!')->error();
            return back();
        }

        view()->share('title', 'Vendor');

        $vendor->load([
            'purchase_orders' => function ($q) {
                $q->orderBy('id', 'desc')->withTrashed();
            },
            'purchase_orders.transactions'
        ]);

        $location_inventory = Batch::InventoryByVendorLocation($vendor->id)->get();
        $inventory_costs = BatchLocationAggregate::inventory_values($vendor->id)->get();

        debug($inventory_costs);

        $nest_inventory = $location_inventory->where('location', 'Nest');
        $all_location_inventory = $location_inventory->whereNotIn('location', ['Nest'])->sortBy('location');

        $pending_order_cost = (new SaleOrder())->pendingOrdersForVendorBatches($vendor->id)->get()->sum('cost_sold');

        $vendor_transactions = OrderTransaction::where('vendor_id', $vendor->id)
            ->orderBy('created_at', 'desc')
            ->with([
                'location',
                'user',
                'purchase_order',
                'children.purchase_order',
                'signature'
            ])
            ->get();

        $total_payments = $vendor->purchase_orders->sum(function($purchase_order) {
            return $purchase_order->transactions->sum('amount');
        });

        return view('users.vendors.show')
            ->with('vendor', $vendor)
            ->with('nest_inventory', $nest_inventory)
            ->with('all_location_inventory', $all_location_inventory)
            ->with('pending_order_cost', $pending_order_cost)
            ->with('total_payments', $total_payments)
            ->with('vendor_transactions', $vendor_transactions)
            ->with('inventory_costs', $inventory_costs)
            ;
    }

    public function statement(Request $request,  User $vendor, VendorStatementFilters $vendorStatementFilters)
    {
        if (Gate::denies('users.view') && Gate::denies('users.view.vendor')) {
            flash('Access Denied!')->error();
            return back();
        }

        view()->share('title', 'Vendor / Transaction Statement');

        $date_presets = date_presets();

        $filters = $vendorStatementFilters->getFilters()->toArray();

        if ($filters) {
            $from = $filters['from'];
            $to = $filters['to'];
        } else {
            $from = Carbon::parse((! empty($request->get('filters')['from']) ? $request->get('filters')['from'] : Carbon::now()))->format('Y-m-d');
            $to = Carbon::parse((! empty($request->get('filters')['to']) ? $request->get('filters')['to'] : Carbon::now()))->format('Y-m-d');
        }

        //get starting balance
        $previous_POs = PurchaseOrder::where('vendor_id', $vendor->id)->whereDate('created_at', '<', $from)->get();

        $previous_vendor_transactions = OrderTransaction::where('vendor_id', $vendor->id)->whereDate('created_at', '<', $from)->get();

        //get transactions from date rante
        $vendor->load([
            'purchase_orders' => function($q) use ($vendorStatementFilters) {
                return $q->filters($vendorStatementFilters);
            }
        ]);

        $vendor_transactions = OrderTransaction::where('vendor_id', $vendor->id)
            ->filters($vendorStatementFilters)
            ->with('signature')
            ->orderBy('created_at', 'desc')
            ->get();

        $all_po_and_txns = $vendor->purchase_orders->merge($vendor_transactions)->sortBy('created_at');

        $pdf=0;
        $view_vars = compact(
            'filters',
            'date_presets',
            'from',
            'to',
            'vendor',
            'previous_vendor_transactions',
            'previous_POs',
            'all_po_and_txns',
            'pdf'
        );

        if($request->get('download_pdf')) {

            $view_vars['pdf'] = 1;

            $pdf = PDF::loadView('users.vendors.statement-pdf', $view_vars);
//
            return $pdf->download(\Str::slug($vendor->name).'-statement.pdf');

        } else {
            return view('users.vendors.statement', $view_vars);

        }

    }

    public function payment(User $vendor, PurchaseOrder $purchaseOrder)
    {
        if (Gate::denies('users.payment.vendor')) {
            flash('Access Denied!')->error();
            return redirect(route('vendors.show', $vendor));
        }

        view()->share('title', 'Vendor / Payment');

        $vendor->load('purchase_orders_with_balance');
        $purchase_orders_with_balance = $vendor->purchase_orders_with_balance->sortBy('id');

        $vendor_data = User::vendors($vendor->id)->payablesSummary()->get();
        $vendor_payable_data = (new User)->aggregatePayablesSummary($vendor_data);

        $total_payments = $vendor->purchase_orders_with_balance->sum(function($purchase_order) {
            return $purchase_order->transactions->sum('amount');
        });

        return view('users.vendors.payment', compact(
            'purchase_orders_with_balance',
            'vendor_payable_data',
            'purchaseOrder',
            'vendor',
            'total_payments'
        ));

    }

    public function storePayment(Request $request, User $vendor)
    {
        if (Gate::denies('users.payment.vendor')) {
            flash('Access Denied!')->error();
            return redirect(route('vendors.show', $vendor));
        }

        try {

            DB::beginTransaction();

            $location_id = $request->get('location_id');

            if(Auth::user()->active_locations->count() > 1 && !$location_id) {
                throw new \Exception('Pay from location is required!');
            }

            $total_payment = (float)request('total_amount');
            $amount_to_credit = (float)request('amount_to_credit');
            $txn_date = request('txn_date');
            $payment_method = request('payment_method');
            $memo = request('memo');
            $ref_number = request('ref_number');
            $txn_fee=0;
            $payment_type = ($payment_method=='Credit'?'applied':'payment');
            $purchase_orders_to_pay = $request->get('purchase_orders');

            $vendor->load('purchase_orders_with_balance');
            $purchase_orders_keyed = $vendor->purchase_orders_with_balance->keyBy('id');

            if($payment_method == 'Credit') {
                if(bccomp($total_payment, $vendor->vendor_credit_balance) == 1) {
                    throw new \Exception("Payment method: Credit. Amount cannot exceed available vendor credit: ".display_currency($vendor->vendor_credit_balance));
                }
            }

            if($request->get('purchase_orders')) {
                $total_purchase_order_amounts_to_pay=0;
                foreach($request->get('purchase_orders') as $po_id => $po_payment_data) {

                    if(!(float)$po_payment_data['amount']) continue;

                    $po_current_balance = $purchase_orders_keyed[$po_id]['journal']['balance']->getAmount()/100;

                    if(!empty($po_payment_data['amount']) && $po_payment_data['amount'] > 0) {
                        //pay amount larger than balance. error
                        if(bccomp((float)$po_payment_data['amount'], $po_current_balance) === 1) {
                            throw new \Exception('Can not pay an amount greater than the purchase order balance. Try again.');
                        }
                    }

                    $total_purchase_order_amounts_to_pay += (float)$po_payment_data['amount'];
                }

                if(bccomp($total_purchase_order_amounts_to_pay, $total_payment) == 1) {
                    throw new \Exception('The Total Amount to pay can\'t be less than the selected bills');
                }

                //compare submitted amounts and total amount
                if(bccomp(bcadd($total_purchase_order_amounts_to_pay, $amount_to_credit), $total_payment) !== 0) {
                    throw new \Exception('There is an error with payment amounts.');
                }
            }

            if($amount_to_credit < 0) {
                throw new \Exception('Cannot have a credit amount less than zero.');
            }

            if(!$total_payment) {
                throw new \Exception("Unable to save request.");
            }

            if(!empty($location_id)) {
                $location = Location::whereId($location_id)->withTrashed()->first();
            } else {
                $location = Auth::user()->active_locations->first();
            }

//            dump($location);
//            dump($txn_date);
//            dump($payment_method);
//            dump($memo);
//            dump($ref_number);
//            dump($payment_type);
//
//            dump('total payment');
//            dump($total_payment);
//
//            dump('total credit');
//            dump($amount_to_credit);
//
//            dump($purchase_orders_keyed);
//
//            dump($purchase_orders_to_pay);
//
//            dd('e');

            $payment = ($payment_type == 'payment' ? $total_payment : ($total_payment * -1));

            $activity_prop = collect([
                'Txn #' => 0,
                'Amount' => display_currency($payment),
                'Method' => $payment_method,
                'Ref Number' => $ref_number,
                'Memo' => $memo,
            ]);

            if($payment_method == 'Credit') {

                if(is_null($purchase_orders_to_pay)) {
                    throw new \Exception("No purchase orders to apply credit to!");
                }

                //parent payment
                $txn_id = $vendor->order_txn_payment(0, $txn_date, $payment_method, $ref_number, $memo, $txn_fee, null, $payment_type, $location, null, $vendor->id);

                $vendor->issue_vendor_credit($payment, $txn_date, $payment_method, $ref_number, $memo, $txn_fee, $txn_id, 'credit', $location);

            } else {

                //parent payment
                $txn_id = $vendor->vendor_payment(ChartOfAccount::Cash()->journal, $vendor->journal, $payment, $txn_date, $payment_method, $ref_number, $memo, $txn_fee, null, $payment_type, $location);

                if($amount_to_credit) { /// payment has a balance, debit vendor first
                    $vendor->issue_vendor_credit($amount_to_credit, $txn_date, 'Credit', $ref_number, $memo, null, $txn_id, 'credit', $location);

                    $activity_prop->put('Saved as credit', display_currency($amount_to_credit));
                }
            }

            if($purchase_orders_to_pay) {
                foreach ($purchase_orders_to_pay as $po_id => $po_payment_data) {
                    if (is_null($po_payment_data['amount'])) continue;

                    $txn = $purchase_orders_keyed[$po_id]->applyPayment($po_payment_data['amount'], $txn_date, $payment_method, $ref_number, $memo, null, $location->id, $txn_fee, $txn_id);

                    $activity_prop->put('Txn #'.$txn->id, display_currency($po_payment_data['amount']).' applied to '.$purchase_orders_keyed[$po_id]->ref_number);
                }
            }

            $activity_prop->put('Txn #', $txn_id);

            activity('vendor')
                ->causedBy(Auth::user())
                ->performedOn($vendor)
                ->withProperties($activity_prop)
                ->log(ucwords($payment_type));

            DB::commit();

            flash()->success('Payment received');

        } catch (\Exception $e) {
            DB::rollBack();
//            dd($e);
            flash()->error($e->getMessage());
        }

        return redirect(route('vendors.show', $vendor));
    }

    public function storeCredit(Request $request, User $vendor)
    {
        if (Gate::denies('users.payment.vendor')) {
            flash('Access Denied!')->error();
            return redirect(route('vendors.show', $vendor));
        }

        try {
            $payment = request('payment');
            $txn_date = request('txn_date');
            $payment_type = request('payment_type');
            $payment_method = request('payment_method');
            $ref_number = request('ref_number');
            $memo = request('memo');
            $location_id = request('location_id');

            if (! $payment) {
                throw new \Exception('Payment amount required!');
            }

            if ($payment_type == 'refund') {
                if ($vendor->vendor_credit_balance == 0) {
                    throw new \Exception('Vendor has no credit to refund!');
                } elseif (bccomp($payment, $vendor->vendor_credit_balance) == 1) {
                    throw new \Exception('Unable to refund more than the vendors credit of: '.display_currency($vendor->vendor_credit_balance));
                }
            }

            if(!empty($location_id)) {
                $location = Location::whereId($location_id)->withTrashed()->first();
            } else {
                $location = Auth::user()->active_locations->first();
            }

            $cash_account = ChartOfAccount::Cash();

            DB::beginTransaction();

            $payment = ($payment_type == 'payment' ? $payment : ($payment * -1));

            $txn_id = $vendor->vendor_payment(ChartOfAccount::Cash()->journal, $vendor->journal, $payment, $txn_date, $payment_method, $ref_number, $memo, 0, null, $payment_type, $location);

            $vendor->issue_vendor_credit($payment, null, 'Credit', null, 'Vendor Credit', null, $txn_id,'credit',$location);

            $activity_prop = collect([
                'Txn #' => $txn_id,
                'Amount' => display_currency($payment),
                'Method' => $payment_method,
                'Ref Number' => $ref_number,
                'Memo' => $memo,
                ($payment>0?'Saved as credit':'Refund') => display_currency($payment)
            ]);

            activity('vendor')
                ->causedBy(Auth::user())
                ->performedOn($vendor)
                ->withProperties($activity_prop)
                ->log(ucwords($payment_type));

            DB::commit();

            flash()->success('Payment received');
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return redirect(route('vendors.show', $vendor));

    }

    public function paymentDestroy(Request $request, User $vendor, OrderTransaction $orderTransaction)
    {

        try {

            DB::beginTransaction();

            $orderTransaction->load([
                'purchase_order',
                'children.purchase_order'
            ]);

            $activity_prop = collect([
                'Txn #' => $orderTransaction->id,
                'Amount' => display_currency($orderTransaction->amount),
                'Method' => $orderTransaction->payment_method,
                'Ref #' => $orderTransaction->ref_number,
            ]);

            //get PO ids that need to be updated
            $po_ids = collect();

            if($orderTransaction->purchase_order) {
                $po_ids->push($orderTransaction->purchase_order->id);
            }

            $children_transactions = $orderTransaction->children;
            $child_po_ids = $children_transactions->pluck('purchase_order_id');
            $po_ids = $po_ids->merge($child_po_ids);


            //log transactions deleted related to purchase orders
            foreach($children_transactions as $children_transaction) {

                if($children_transaction->purchase_order_id) {

                    $activity_prop->put('Txn #'.$children_transaction->id, display_currency($children_transaction->amount).' payment deleted '.$children_transaction->purchase_order->ref_number);

                    $po_activity_prop = collect([
                        'Txn #' => $children_transaction->id,
                        'Amount' => display_currency($children_transaction->amount),
                        'Method' => $children_transaction->payment_method,
                        'Ref #' => $children_transaction->ref_number,
                    ]);
                    activity('purchase-order')
                        ->causedBy(Auth::user())
                        ->performedOn($children_transaction->purchase_order)
                        ->withProperties($po_activity_prop)
                        ->log(ucwords('transaction deleted'));
                } elseif($children_transaction->type == 'credit') {

                    $activity_prop->put('Credit Reversed', display_currency($children_transaction->amount));

                }

            }

            //get transactions that need to be deleted...
            $txn_ids = collect($orderTransaction->acct_journal_txn_fid);
            $txn_ids = $txn_ids->merge($children_transactions->pluck('acct_journal_txn_fid'));

            foreach($txn_ids as $txn_id) {
                $txn_record = JournalTransaction::where('acct_journal_txn_pid', $txn_id)->first();
                if($txn_record->transaction_group) {
                    $txn_record2 = JournalTransaction::where('transaction_group', $txn_record->transaction_group)
                        ->where('acct_journal_txn_pid', '!=', $txn_id)->first();
                    $txn_ids->push($txn_record2->acct_journal_txn_pid);
                }
            }

            /////legacy payment..need to debit/credit vendor journal
            if($orderTransaction->vendor_id && $orderTransaction->purchase_order_id) {
                $payment_money = convert_to_cents($orderTransaction->amount, true, true);
                if($orderTransaction->amount > 0) { //credit
                    $vendor->journal->credit($payment_money);
                } else { //debit
                    $vendor->journal->debit($payment_money);
                }
            }

            JournalTransaction::whereIn('acct_journal_txn_pid', $txn_ids->toArray())->delete();

            $orderTransaction->delete();

            $purchase_orders = PurchaseOrder::whereIn('id', $po_ids->toArray())->with('journal')->get();

            foreach($purchase_orders as $purchase_order) {
                $purchase_order->journal->resetCurrentBalances();
                if($purchase_order->balance) {
                    $purchase_order->status = 'open';
                    $purchase_order->save();
                }
            }

            ChartOfAccount::VendorCredits()->journal->resetCurrentBalances();

            $vendor->journal->resetCurrentBalances();

            activity('vendor')
                ->causedBy(Auth::user())
                ->performedOn($vendor)
                ->withProperties($activity_prop)
                ->log(ucwords('transaction deleted'));

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();
            flash()->error($e->getMessage());

        }

        return redirect(route('vendors.show', $vendor));
    }

    public function transactionsPaidSignature(Request $request, User $vendor, OrderTransaction $orderTransaction)
    {
        view()->share('title', 'Vendor / Cash Pickup');

        $orderTransaction->load([
            'purchase_order',
            'signature',
        ]);

        return view('users.vendors.transactions.paid_signature', compact(
            'vendor',
            'orderTransaction'
        ));
    }

    public function transactionsPaidSignatureStore(Request $request, User $vendor, OrderTransaction $orderTransaction)
    {
        $signature = new OrderTransactionSignatures([
            'user_id' => Auth::user()->id,
            'name' => $request->get('name'),
            'signature_png' => $request->get('signature_image'),
        ]);

        $orderTransaction->signature()->save($signature);

        return redirect(route('vendors.show', $orderTransaction->vendor_id));
    }

    public function activityLog(User $vendor)
    {
        view()->share('title', 'Vendor / Activity Log');

        $vendor->load('activity_logs.causer');

        $heading3 = $vendor->name;

        $back_link = route('vendors.show', $vendor->id);

        return view('activity-log', [
            'model'=>$vendor,
        ], compact( 'heading3', 'back_link'));
    }

}
