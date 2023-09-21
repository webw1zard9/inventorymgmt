<?php

namespace App\Http\Controllers;

use App\Batch;
use App\BatchLocation;
use App\Broker;
use App\ChartOfAccount;
use App\Events\SaleOrderDelivered;
use App\Exports\SaleOrderExport;
use App\Filters\SaleOrderFilters;
use App\Location;
use App\Order;
use App\OrderDetail;
use App\Repositories\Contracts\SaleOrderRepositoryInterface;
use App\Repositories\DbUserRepository;
use App\SaleOrder;
use App\User;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Money\Currency;
use Money\Money;
use Scottlaurent\Accounting\Services\Accounting;


class SaleOrdersController extends Controller
{
    public function __construct(SaleOrderRepositoryInterface $saleOrderRepositoryInterface)
    {
        parent::__construct();

//        $this->sale_order = $saleOrderRepositoryInterface;
    }

    public function index(Request $request, SaleOrderFilters $saleOrderFilters)
    {
        $sale_orders = SaleOrder::filters($saleOrderFilters)->with([
            'customer',
            'bill_to',
            'sales_rep',
            'user',
            'order_details_cog.sale_order',
            'order_details_cog.batch.fund',
            'order_details.batch_location',
            'location',
            'journal',
        ])
            ->withTrashed()
            ->orderBy('id', 'desc')
            ->paginate(20);

        $filters = $saleOrderFilters->getFilters()->toArray();

        $filter_customer = null;
        if (isset($filters['customer'])) {
            $filter_customer = User::find($filters['customer']);
        }

        $customers = User::customers()->get();
        $sales_reps = User::salesrep()->get();
//        $brokers = Broker::orderBy('name')->pluck('name','id');

        $order_discounts = SaleOrder::where('discount_approved', 0)->count();
        $order_lines_discounts = BatchLocation::needApproval()->count();

        $warnings = collect();

        if (Auth::user()->level() >= 60 && ($order_discounts || $order_lines_discounts)) {
            $warnings->push('Some orders need discount approvals. <a href="'.route('sale-orders.discount-approval').'">Click Here</a>');
        }

        return view('sale_orders.index', compact('sale_orders', 'filters', 'customers', 'sales_reps', 'filter_customer', 'warnings'));
    }

    public function show(SaleOrder $saleOrder)
    {
        if (Gate::denies('so.show')) {
            flash()->error('Access Denied');
            return back();
        }
        view()->share('title', 'Sale Order');

//        $saleOrder->journal->resetCurrentBalances();
        $saleOrder->load([
            'sales_rep',
            'broker',
            'order_details.batch',
            'order_details.batch.locations_aggregate' => function($q) use ($saleOrder) {
                $q->where('location_id', $saleOrder->location_id);
            },
            'order_details.sale_order',
            'order_details.batch.category',
//            'order_details.batch.fund',
//            'order_details.batch.allocated_inventory',
//            'order_details.batch.parent_batch',
//            'order_details.batch.fund',
            'order_details.batch_location',
            'order_details.fulfill_activity_log.causer',

        ]);

        $sales_reps = (new DbUserRepository)->sales_reps()->pluck('name', 'id');

        return view('sale_orders.show', compact(
            'saleOrder',
            'sales_reps'
        ));
    }

    public function store(Request $request)
    {

        try {

            DB::beginTransaction();

            $data = [
                'user_id' => Auth::user()->id,
                'customer_id' => $request->get('destination_user_id'),
                'sales_rep_id' => $request->get('sales_rep_id', (Auth::user()->hasRole('salesrep') ? Auth::user()->id : null)),
                'location_id' => $request->has('location_id') ? $request->get('location_id') : Auth::user()->active_locations->first()->id,
                'txn_date' => $request->get('txn_date', Carbon::now()->format('Y-m-d')),
                'sale_type' => null,
                'status' => 'hold',
            ];

            $sale_order = SaleOrder::create($data);
            $sale_order->set_order_id();
            $sale_order->calculateTotals();

            DB::commit();

            activity('sale-order')
                ->causedBy(Auth::user())
                ->performedOn($sale_order)
                ->log('Created');

            return redirect(route('sale-orders.show', $sale_order));

        } catch(\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
            return redirect(route('sale-orders.index'));
        }
    }

    public function discountApproval(Request $request)
    {
        view()->share('title', 'Sale Order / Discount Approvals');

        $order_discount_approvals = SaleOrder::requiresDiscountApproval()->get();
        $order_discount_line_approvals = SaleOrder::requiresDiscountLineApproval()->get();

        $all_order_discounts_approval = $order_discount_approvals->merge($order_discount_line_approvals);
//        dd($all_order_discounts_approval);
        return view('sale_orders.discount_approval', compact('all_order_discounts_approval'));
    }





    public function update(Request $request, SaleOrder $saleOrder)
    {
        $saleOrder->status = $request->has('status') ? $request->get('status') : $saleOrder->status;
        $saleOrder->inv_number = ($request->has('inv_number') ? $request->get('inv_number') : $saleOrder->inv_number);
        $saleOrder->manifest_no = ($request->has('manifest_no') ? $request->get('manifest_no') : $saleOrder->manifest_no);
        $saleOrder->terms = ($request->has('terms') ? $request->get('terms') : $saleOrder->terms);
        $saleOrder->txn_date = ($request->has('txn_date') ? $request->get('txn_date') : $saleOrder->txn_date);

        $saleOrder->sales_rep_id = ($request->has('sales_rep_id') ? $request->get('sales_rep_id') : $saleOrder->sales_rep_id);
        $saleOrder->broker_id = ($request->has('broker_id') ? $request->get('broker_id') : $saleOrder->broker_id);

        $saleOrder->expected_delivery_date = ($request->has('expected_delivery_date') ? $request->get('expected_delivery_date') : $saleOrder->expected_delivery_date);

        $saleOrder->setDueDate();

//        $saleOrder->due_date = $saleOrder->txn_date->addDays($saleOrder->terms);

        $saleOrder->notes = $request->has('notes') ? $request->get('notes') : $saleOrder->notes;
        $saleOrder->order_notes = $request->has('order_notes') ? $request->get('order_notes') : $saleOrder->order_notes;
        $saleOrder->in_qb = $request->has('in_qb') ? $request->get('in_qb') : $saleOrder->in_qb;
        $saleOrder->save();

        if (request()->wantsJson()) {
            return response()->json($saleOrder);
        }
        flash()->success('Sales Order Updated!');

        return redirect(route('sale-orders.show', $saleOrder->id));
    }

    public function applyDiscount(Request $request, SaleOrder $saleOrder)
    {
        if ($request->has('delete')) {
            $request->merge([
                'discount_type' => 'none',
                'discount_applied' => 0,
                'discount_description' => null,
            ]);
        }

        switch ($request->get('discount_type')) {
            case 'perc':
                if ($request->get('discount_applied') <= 0 || $request->get('discount_applied') > 100) {
                    $request->flash();
                    flash()->error('Discount percentage out of range.');

                    return redirect(route('sale-orders.show', $saleOrder->id));
                }
                break;
            case 'amt':
                if ($request->get('discount_applied') <= 0) {
                    $request->flash();
                    flash()->error('Invalid discount amount.');

                    return redirect(route('sale-orders.show', $saleOrder->id));
                }
                break;
            default:
                if ($request->get('discount_applied')) {
                    $request->flash();
                    flash()->error('Please select a discount type if applying a discount.');

                    return redirect(route('sale-orders.show', $saleOrder->id));
                }

        }
        //dump($request->get('discount_applied', 0));
        $saleOrder->discount_description = ($request->get('discount_type') == 'none' ? '' : $request->get('discount_description'));
        $saleOrder->discount_applied = $request->get('discount_applied', 0);
        $saleOrder->discount_type = $request->get('discount_type');
        //dd($saleOrder);
//        dump($saleOrder->discount_applied);
//        dd('te');
//        $saleOrder->save();

//        dump('disc applied');
//        dump($saleOrder->discount_applied);
        if (Auth::user()->hasRole('salesrep')) {
            $saleOrder->discount_approved = 0;
        }

        $activity_prop = collect();

        if ($saleOrder->discount_applied) {
            if ($saleOrder->discount_type == 'perc') {
                $saleOrder->discount = $saleOrder->subtotal * ($saleOrder->discount_applied / 100);
                $activity_amount = $saleOrder->discount_applied.'%';
            } else {
                $saleOrder->discount = $saleOrder->discount_applied;
                $activity_amount = display_currency($saleOrder->discount);
            }

            $activity_prop = collect([
                'Amount' => $activity_amount,
                'Discount Description' => $saleOrder->discount_description,
            ]);
            $activity_log_name = 'Apply Discount';
        } else {
            $saleOrder->discount = 0;
            $saleOrder->discount_approved = 1;
            $activity_log_name = 'Remove Discount';
        }

        $saleOrder->save();
        $saleOrder->calculateTotals();

        activity('sale-order')
            ->causedBy(Auth::user())
            ->performedOn($saleOrder)
            ->withProperties($activity_prop)
            ->log($activity_log_name);

        flash()->success('Discount applied');

        return redirect(route('sale-orders.show', $saleOrder->id));
    }

    public function approveDiscount(SaleOrder $saleOrder)
    {
        //auth middleware in routes - web.php

        try {
            DB::beginTransaction();

            $saleOrder->approveDiscount();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return back();
//        return redirect(route('sale-orders.show', $saleOrder));
    }

    public function rejectDiscount(SaleOrder $saleOrder)
    {
        //auth middleware in routes - web.php

        try {
            DB::beginTransaction();

            $saleOrder->rejectDiscount();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return redirect(route('sale-orders.show', $saleOrder));
    }

    public function invoice(SaleOrder $saleOrder)
    {
        $saleOrder->load('order_details.batch.category', 'bill_to', 'order_details.batch.brand', 'order_details.sale_order');

        $pdf = PDF::loadView('sale_orders.invoice', compact('saleOrder'));

//        return view('sale_orders.invoice', compact('saleOrder'));

        return $pdf->download(\Str::slug($saleOrder->customer->name).'-'.$saleOrder->ref_number.'.pdf');
    }

    public function shippingManifest(SaleOrder $saleOrder)
    {
        $saleOrder->load('order_details.batch.category');

        return view('sale_orders.shipping-manifest', compact('saleOrder'));
    }

    public function remove(SaleOrder $saleOrder)
    {
        try {

            //check order for items

            if($saleOrder->order_details->count()) {
                throw new \Exception("Cannot void. Order has items.");
            }

            DB::beginTransaction();

            //remove any discounts
            $saleOrder->discount = 0;
            $saleOrder->discount_description = '';
            $saleOrder->discount_applied = 0;
            $saleOrder->discount_type = '';
            $saleOrder->discount_approved = 1;
            $saleOrder->save();
            $saleOrder->calculateTotals();

            if ($saleOrder->balance < 0) { //issue customer credit

                $so_balance_money = convert_to_cents(abs($saleOrder->balance), true);
                $so_balance_cents = convert_to_cents(abs($saleOrder->balance));

                $transaction_group = Accounting::newDoubleEntryTransactionGroup();
                $transaction_group->addTransaction(ChartOfAccount::PrepaidInventory()->journal, 'debit', $so_balance_money, null, $saleOrder->customer);
                $transaction_group->addTransaction($saleOrder->customer->journal, 'credit', $so_balance_money, null, $saleOrder);
                $transaction_group_id = $transaction_group->commit();

                $saleOrder->customer->journal->resetCurrentBalances();

                $txn = $saleOrder->journal->credit($so_balance_cents);
                $txn->refresh();

                $saleOrder->applyPayment($saleOrder->balance, Carbon::now(), 'Credit', null, 'System issued due to over payment.', $txn->acct_journal_txn_pid, $saleOrder->location_id);

                $saleOrder->journal->resetCurrentBalances();
            }

            activity('sale-order')
                ->causedBy(Auth::user())
                ->performedOn($saleOrder)
//                ->withProperties($discount_details)
                ->log('Voided');

            $saleOrder->status = 'voided';
            $saleOrder->save();
            $saleOrder->delete();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return redirect(route('sale-orders.show', $saleOrder->id));
    }

    public function restore(SaleOrder $saleOrder)
    {
        try {
            DB::beginTransaction();

            activity('sale-order')
                ->causedBy(Auth::user())
                ->performedOn($saleOrder)
//                ->withProperties($discount_details)
                ->log('Restored');

            $saleOrder->status = 'hold';
            $saleOrder->save();
            $saleOrder->restore();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());

            return redirect(route('sale-orders.show', $saleOrder->id));
        }

        return redirect(route('sale-orders.show', $saleOrder->id));
    }

    public function removeAllItems(SaleOrder $saleOrder)
    {
//        dd($saleOrder->order_details);

        try {

            DB::beginTransaction();

            foreach($saleOrder->order_details as $order_detail) {
                $saleOrder->removeItem($order_detail);
            }

            $saleOrder->refresh();

            $saleOrder->calculateTotals();

            DB::commit();

            return redirect(route('sale-orders.show', $saleOrder));

        } catch(\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
            return redirect(route('sale-orders.show', $saleOrder));
        }



    }

//    public function removeItem(SaleOrder $saleOrder, OrderDetail $orderDetail)
//    {
//        if ($saleOrder->id != $orderDetail->sale_order_id) {
//            flash()->error('Product sale order doesn\'t match');
//
//            return redirect(route('sale-orders.show', $saleOrder->id));
//        }
//
//        try {
//            DB::beginTransaction();
//
//            $removeitem = $saleOrder->removeItem($orderDetail);
//
//            dd($removeitem);
//            if ($removeitem instanceof OrderDetail) {
//                $saleOrder->calculateTotals();
//
//                DB::commit();
//
//                return redirect(route('sale-orders.show', $saleOrder->id));
//            } else {
//                DB::rollBack();
//
//                flash()->error($removeitem->getMessage());
//
//                return redirect(route('sale-orders.show', $saleOrder->id));
//            }
//        } catch (\Exception $e) {
//            DB::rollBack();
//            flash()->error($e->getMessage());
//
//            return redirect(route('sale-orders.show', $saleOrder->id));
//        }
//    }

//    public function fulfillOrderDetail(Request $request, SaleOrder $saleOrder)
//    {
//        try {
//            $errors = collect();
//            //dd($request->all());
//            DB::beginTransaction();
//
//            foreach ($request->all() as $order_detail_info) {
//                $orderDetail = OrderDetail::find(explode('-', $order_detail_info['name'])[1]);
//
//                $units_fulfilled = $order_detail_info['value'];
//
////                dd($units_fulfilled);
//
//                if ($units_fulfilled < 0) {
//                    $errors->push(true);
//                    flash()->error($orderDetail->batch->name.' - Must be greater than 0');
//                    continue;
//                }
//
//                if (
//                    bccomp($units_fulfilled, $orderDetail->units) > 0 ||
//                    (bccomp(abs($orderDetail->batch_location->quantity), $units_fulfilled, 4) < 0)
//                ) {
//                    $errors->push(true);
//                    flash()->error($orderDetail->batch->name.' - Cannot fulfill an amount greater than ordered!');
////                    Bugsnag::notifyException(new \Exception("There was an error fulfilling! Try again."));
//                    continue;
//                }
//
//                $orderDetail->units_fulfilled = $units_fulfilled;
//
//                $orderDetail->push();
//
//                if ($orderDetail->getChanges()) {
//                    $activity_prop = collect([
//                        'Batch ID' => $orderDetail->batch->id,
//                        'SKU' => $orderDetail->batch->ref_number,
//                        'Name' => $orderDetail->sold_as_name,
//                        'Ordered Qty' => $orderDetail->units.' '.$orderDetail->batch->uom,
//                        'Fulfilled Qty' => (is_null($orderDetail->units_fulfilled) ? 'NULL' : $orderDetail->units_fulfilled.' '.$orderDetail->batch->uom),
//                    ]);
//
//                    activity('sale-order')
//                        ->withProperties($activity_prop)
//                        ->causedBy(Auth::user())
//                        ->performedOn($orderDetail->sale_order)
//                        ->log('Fulfill Item');
//                }
//            }
//
//            if ($saleOrder->order_details_not_fulfilled()->count() == 0) {
//                $saleOrder->ready_for_delivery();
//            }
//
//            DB::commit();
//        } catch (\Exception $e) {
//            DB::rollBack();
//            flash()->error($e->getMessage());
//        }
//
//        $response_code = ($errors->count() ? 400 : 200);
//
//        return response([
//            'error' => $errors->toArray(),
//        ], $response_code);
//    }

//    public function acceptOrderDetail(Request $request, SaleOrder $saleOrder)
//    {
//        try {
//
//            $errors = collect();
//
//            DB::beginTransaction();
//
//            foreach($request->all() as $order_detail_info) {
//
//                if (is_null($order_detail_info['value'])) continue;
//
//                $orderDetail = OrderDetail::find(explode("-", $order_detail_info['name'])[1]);
//
//                $units_accepted = $order_detail_info['value'];
//
    ////                $units_accepted = (float)request('units_accepted');
//
//                if ($units_accepted < 0) {
//                    $errors->push("must be greater than 0");
//                    continue;
    ////                    throw new \Exception("Value must be greater than 0");
//                }
//
//                if (bccomp($units_accepted, $orderDetail->units) > 0) {
//                    $errors->push("Cannot fulfill an amount greater than ordered!");
//                    continue;
    ////                    throw new \Exception("Cannot fulfill an amount greater than ordered!");
//                }
//
//                $units_rejected = bcsub($orderDetail->units, $units_accepted);
//
//                $batch_location_qty_b = $orderDetail->batch_location->quantity;
//                $batch_inv_qty_b = $orderDetail->batch->inventory;
//
//                if ($units_rejected > 0) {
//                    $orderDetail->batch_location->quantity = bcadd($orderDetail->batch_location->quantity, $units_rejected);
//                    $orderDetail->batch->inventory = bcadd($orderDetail->batch->inventory, $units_rejected);
//                }
//
//                $orderDetail->units_accepted = $units_accepted;
//
//                $activty_data = [
//                    'units_rejected' => $units_rejected,
//                    'batch_location_qty_b' => $batch_location_qty_b,
//                    'batch_location_qty_a' => $orderDetail->batch_location->quantity,
//                    'batch_inv_qty_b' => $batch_inv_qty_b,
//                    'batch_inv_qty_a' => $orderDetail->batch->inventory,
//                ];
    ////
//                activity()
    ////                    ->withProperties($activty_data)
//                    ->causedBy(Auth::user())
//                    ->performedOn($orderDetail)
//                    ->log('Fulfill Item');
//
//                if (bccomp(abs($orderDetail->batch_location->quantity), $orderDetail->units_accepted, 4) !== 0) {
//                    $errors->push("Cannot fulfill an amount greater than ordered!");
//
//                    $ex = new \Exception("There was an error fulfilling! Try again.");
//                    Bugsnag::notifyException($ex);
//
//                    continue;
//                }
//
//                $orderDetail->push();
//
//                $activity_prop = collect([
//                    'Batch ID'=>$orderDetail->batch->id,
//                    'SKU'=>$orderDetail->batch->ref_number,
//                    'Name'=>$orderDetail->sold_as_name,
//                    'Ordered Qty'=>$orderDetail->units." ".$orderDetail->batch->uom,
//                    'Fulfilled Qty'=>$orderDetail->units_accepted." ".$orderDetail->batch->uom,
//                ]);
//
//                activity('sale-order')
//                    ->withProperties($activity_prop)
//                    ->causedBy(Auth::user())
//                    ->performedOn($orderDetail->sale_order)
//                    ->log('Fulfill Item');
//
//            }
//
//
//            if($saleOrder->order_details()->whereNull('units_accepted')->count() == 0)
//            {
//                $saleOrder->ready_for_delivery();
//            }
//
//            $saleOrder->calculateTotals();
    ////
    ////            if($errors->count()) {
    ////                flash()->error("There were some errors!");
    ////            }
//
//            DB::commit();
//
//        } catch(\Exception $e) {
//            DB::rollBack();
//            flash()->error($e->getMessage());
//        }
//
//        $response_code = ($errors->count()?400:200);
//
//        return response([
//            'error'=>$errors->toArray(),
//        ],$response_code);
//
//    }

    public function undoFulfillment(SaleOrder $saleOrder, OrderDetail $orderDetail)
    {
        try {
            DB::beginTransaction();

            $activity_prop = collect([
                'Batch ID' => $orderDetail->batch->id,
                'SKU' => $orderDetail->batch->ref_number,
                'Name' => $orderDetail->sold_as_name,
                'Ordered Qty' => $orderDetail->units.' '.$orderDetail->batch->uom,
                'Undo Qty' => $orderDetail->units_accepted.' '.$orderDetail->batch->uom,
            ]);

            $qty_change = bcsub($orderDetail->units_accepted, $orderDetail->units,4);

            $orderDetail->batch_location->quantity = bcadd($orderDetail->batch_location->quantity, $qty_change,4);
            $orderDetail->batch->inventory = bcadd($orderDetail->batch->inventory, $qty_change,4);

            if ($orderDetail->batch->inventory < 0) {
                throw new \Exception('Can not undo. There is no inventory avialable.');
            }

            $orderDetail->units_fulfilled = null;
            $orderDetail->units_accepted = null;

            $orderDetail->push();

            $saleOrder->calculateTotals();

            activity('sale-order')
                ->causedBy(Auth::user())
                ->performedOn($saleOrder)
                ->withProperties($activity_prop)
                ->log('Undo Fulfill Item');

            DB::commit();

            flash()->success('Fulfillment Undone');
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return redirect(route('sale-orders.show', $saleOrder->id));
    }

//    public function acceptAll(SaleOrder $saleOrder)
//    {
//        $saleOrder->order_details->map(function($order_detail) {
//            if(empty($order_detail->batch)) return;
//            if( ! is_null($order_detail->units_accepted)) return;
//            $order_detail->units_accepted = $order_detail->units;
//            $order_detail->save();
//        });
//
//        $saleOrder->status = 'ready for delivery';
//        $saleOrder->save();
//
//        event(new SaleOrderDelivered($saleOrder));
//
//        return back();
//    }

    public function deliverOrder(SaleOrder $saleOrder)
    {
        try {
            DB::beginTransaction();

            $saleOrder->delivered();

            if ($saleOrder->balance < 0) { //issue customer credit

                $amount = convert_to_cents(abs($saleOrder->balance), true);

                $transaction_group = Accounting::newDoubleEntryTransactionGroup();
                $transaction_group->addTransaction(ChartOfAccount::PrepaidInventory()->journal, 'debit', $amount, null, $saleOrder->customer);
                $transaction_group->addTransaction($saleOrder->customer->journal, 'credit', $amount, null, $saleOrder);
                $transaction_group_id = $transaction_group->commit();

                $txn = $saleOrder->journal->credit(convert_to_cents(abs($saleOrder->balance)));
                $txn->refresh();

                $saleOrder->applyPayment($saleOrder->balance, Carbon::now(), 'Credit', null, 'System issued due to over payment.', $txn->acct_journal_txn_pid, $saleOrder->location_id);

                $saleOrder->journal->resetCurrentBalances();
            }

            event(new SaleOrderDelivered($saleOrder));

            foreach($saleOrder->order_details as $order_detail) {
                DB::select("SET @batch_id := ?", [$order_detail->batch_id]);
                DB::select("CALL sync_batch_location_aggregate()");
            }

            DB::commit();

            flash()->success('Delivered');

            return redirect(route('sale-orders.index'));

        } catch (\Exception $e) {
            DB::rollBack();
            Bugsnag::notifyException($e);
            flash()->error('There is an issue with this order and cannot be delivered! Please fix. - '.$e->getMessage());
        }

        return redirect(route('sale-orders.show', $saleOrder));
    }

    public function close(SaleOrder $saleOrder)
    {
        $saleOrder->close();

        return back();
    }

    public function open(SaleOrder $saleOrder)
    {
        $saleOrder->open();

        return back();
    }

    public function refreshBalance(SaleOrder $saleOrder)
    {

        $saleOrder->journal->resetCurrentBalances();
        return back();

    }

    public function hold(SaleOrder $saleOrder)
    {

        //reverse accounting journals
        if ($saleOrder->status == 'delivered') {
            $transaction_group = Accounting::newDoubleEntryTransactionGroup();

            $sale_order_total = convert_to_cents($saleOrder->total, true);

            if ($saleOrder->total) {
                $transaction_group->addTransaction(ChartOfAccount::PrepaidInventory()->journal, 'credit', $sale_order_total, null, $saleOrder->customer);  // your user journal probably is an income ledger
                $transaction_group->addTransaction(ChartOfAccount::Revenue()->journal, 'debit', $sale_order_total, null, $saleOrder->location); // this is an asset ledder
            }

            //credit cogs
            //debit inventory
            $sale_order_cost = convert_to_cents($saleOrder->cost, true);
            if ($saleOrder->cost) {
                $transaction_group->addTransaction(ChartOfAccount::COG()->journal, 'credit', $sale_order_cost, null, $saleOrder->location);  // your user journal probably is an income ledger
                $transaction_group->addTransaction(ChartOfAccount::Inventory()->journal, 'debit', $sale_order_cost, null, $saleOrder->location); // this is an asset ledder
            }

            $transaction_group->commit();
        }

        $saleOrder->hold();
        $saleOrder->log_status_activity('Reverse Hold');

        return back();
    }

    public function readyToPack(SaleOrder $saleOrder)
    {
        $saleOrder->ready_to_pack();

        return back();
    }

    public function readyForDelivery(SaleOrder $saleOrder)
    {
        try {
            DB::beginTransaction();

            $saleOrder->ready_for_delivery();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return back();
    }

    public function inTransit(SaleOrder $saleOrder)
    {
        $saleOrder->in_transit();

        return back();
    }

    public function payment(Request $request, SaleOrder $saleOrder)
    {
        try {

//            dd($request->all());

            $so_payment = $orig_full_payment = request('payment');
            $parent_id = request('parent_id', null);
            $txn_fee = request('txn_fee');
            $txn_date = request('txn_date');
            $payment_type = request('payment_type');
            $payment_method = request('payment_method');
            $ref_number = request('ref_number');
            $memo = request('memo');

            if (is_null($so_payment) || $so_payment <= 0) {
                throw new \Exception('Can not post a payment!');
            }

            $payment_money = convert_to_cents(abs($so_payment), true);
            $payment_cents = convert_to_cents(abs($so_payment));

            DB::beginTransaction();

            if ($payment_type == 'payment') {
                if ($payment_method != 'Credit') { //Cash/BTC/ETH

//                    if($saleOrder->balance <= 0) {
//                        throw new \Exception("Can not post payment! If you would like to issue a customer credit, issue payment from the <a href='".route('users.show', $saleOrder->customer)."'>customer profile page.</a>");
//                    }

                    if (in_array($payment_method, config('inventorymgmt.crypto_payment_methods')) && ! $ref_number) {
                        throw new \Exception('Crypto amount required!');
                    }

                    if (! in_array($payment_method, config('inventorymgmt.crypto_payment_methods')) && $ref_number) {
                        throw new \Exception('Method must be a crypto type!');
                    }

                    if ($so_payment > 0) {
                        // this represents payment in cash to satisy that AR entry
                        $transaction_group = Accounting::newDoubleEntryTransactionGroup();
                        $transaction_group->addTransaction(ChartOfAccount::Cash()->journal, (($so_payment > 0) ? 'debit' : 'credit'), $payment_money, null, $saleOrder->location);
                        $transaction_group->addTransaction(ChartOfAccount::PrepaidInventory()->journal, (($so_payment > 0) ? 'credit' : 'debit'), $payment_money, null, $saleOrder->customer);
                        $transaction_group->commit();

                        if ($so_payment >= 0) {
                            $txn = $saleOrder->journal->debit($payment_cents);
                        } else {
                            $txn = $saleOrder->journal->credit($payment_cents);
                        }

                        $txn->refresh();

                        $saleOrder->customer->journal->resetCurrentBalances();
                        $saleOrder->journal->resetCurrentBalances();

                        $ref_number_pro_rata = ($ref_number ? ($ref_number * ($so_payment / $orig_full_payment)) : null);

                        $saleOrder->applyPayment($so_payment, $txn_date, $payment_method, $ref_number_pro_rata, $memo, $txn->acct_journal_txn_pid, $saleOrder->location_id, $txn_fee, $parent_id);
                    }

                    flash()->success('Customer payment applied!');
                } else { ///Credit

                    if ($so_payment > $saleOrder->balance) {
                        throw new \Exception("Can't apply more credit than order balance: ".display_currency($saleOrder->balance));
                    }

                    if ($so_payment > ($saleOrder->customer->available_balance)) {
                        throw new \Exception("Can't apply more credit than available: ".display_currency($saleOrder->customer->available_balance));
                    }

                    $transaction_group = Accounting::newDoubleEntryTransactionGroup();
                    $transaction_group->addTransaction($saleOrder->customer->journal, 'debit', $payment_money, null, $saleOrder);
                    $transaction_group->addTransaction(ChartOfAccount::PrepaidInventory()->journal, 'credit', $payment_money, null, $saleOrder->customer);
                    $transaction_group->commit();

                    $txn = $saleOrder->journal->debit($payment_cents);
                    $txn->refresh();

                    $saleOrder->customer->journal->resetCurrentBalances();
                    $saleOrder->journal->resetCurrentBalances();

                    $saleOrder->applyPayment($so_payment, $txn_date, $payment_method, $ref_number, $memo, $txn->acct_journal_txn_pid, null, $parent_id);

                    flash()->success('Customer credit applied!');
                }
            } elseif ($payment_type == 'refund') {
                $refund_amount = $so_payment;

                $total_possible_refund_amount = $so_prepaid_amount = ($saleOrder->total - $saleOrder->balance);

//                $total_possible_refund_amount = $so_prepaid_amount - $saleOrder->customer->balance;

                if ($so_prepaid_amount == 0) {
                    throw new \Exception('Unable to refund.');
                }

                if ($payment_method == 'Credit' && $refund_amount > $so_prepaid_amount) {
                    throw new \Exception("Can't credit back more than is pre-paid on this order! (".display_currency($so_prepaid_amount).')');
                }

                if ($refund_amount > $total_possible_refund_amount) {
                    throw new \Exception('Unable to refund: '.display_currency($refund_amount).'. Max refund amount: '.display_currency($total_possible_refund_amount));
                }

//                dump($saleOrder->customer->balance);
//                dump($so_prepaid_amount);
//                dump($total_possible_refund_amount);
//                dd($refund_amount);

                $cust_credit_refund = 0;
                if ($refund_amount > $so_prepaid_amount) { //split refund
                    $cust_credit_refund = ($refund_amount - $so_prepaid_amount);
                    $refund_amount = $so_prepaid_amount;
                }

                if ($refund_amount) {
                    $refund_money = convert_to_cents(abs($refund_amount), true);
                    $refund_cents = convert_to_cents(abs($refund_amount));

                    if ($payment_method == 'Credit') {
                        $refund_account = $saleOrder->customer;
                    } else {
                        $refund_account = ChartOfAccount::Cash();
                    }

                    $transaction_group = Accounting::newDoubleEntryTransactionGroup();
                    $transaction_group->addTransaction(ChartOfAccount::PrepaidInventory()->journal, 'debit', $refund_money, null, $saleOrder->customer);

                    if ($payment_method == 'Credit') {
                        $transaction_group->addTransaction($saleOrder->customer->journal, 'credit', $refund_money, null, $saleOrder);
                    } else {
                        $transaction_group->addTransaction(ChartOfAccount::Cash()->journal, 'credit', $refund_money, null, $saleOrder->location);
                    }

                    $transaction_group->commit();

                    $txn = $saleOrder->journal->credit($refund_cents);
                    $txn->refresh();

                    $saleOrder->applyPayment(($refund_amount * -1), $txn_date, $payment_method, ($ref_number * -1), $memo, $txn->acct_journal_txn_pid, $saleOrder->location_id, ($txn_fee * -1), $parent_id);
                }

                if ($cust_credit_refund) {
                    //will use location for their cash account
                    $location = Location::find($saleOrder->location_id);
                    $memo = 'Customer credit refund from '.$saleOrder->ref_number;
                    $saleOrder->customer->payment($location, -$cust_credit_refund, $txn_date, $payment_method, null, $memo);
                }

                $saleOrder->customer->journal->resetCurrentBalances();
                $saleOrder->journal->resetCurrentBalances();

                flash()->success('Customer refund successful!');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
        }

        return redirect(route('sale-orders.show', $saleOrder->id));
    }

    public function resetFilters(SaleOrderFilters $saleOrderFilters)
    {
        $saleOrderFilters->resetFilters();

        return redirect(route('sale-orders.index'));
    }


    public function export(Request $request, SaleOrderFilters $saleOrderFilters)
    {
        $sale_orders = SaleOrder::filters($saleOrderFilters)->with([
            'customer',
            'bill_to',
            'sales_rep',
            'user',
            'order_details_cog.sale_order',
            'order_details.batch_location',
            'location',
            'journal',
        ])
            ->orderBy('txn_date', 'desc')
            ->get();

        $filename_parts = collect([
            'Sale-Orders',
            Carbon::now(),
        ]);

        $filename = $filename_parts->implode('-').'.xlsx';

        return Excel::download(new SaleOrderExport($sale_orders), $filename);
    }

    public function activityLog(Request $request, SaleOrder $model)
    {
        view()->share('title', 'Sale Order / Activity Log');

        $model->load('activity_logs.causer');

        $heading3 = $model->ref_number;

        $back_link = route('sale-orders.show', $model->id);

        return view('activity-log', compact('model', 'heading3', 'back_link'));
    }
}
