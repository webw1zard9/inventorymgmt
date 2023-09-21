<?php

namespace App\Http\Controllers;

use App\Batch;
use App\BatchLocation;
use App\Brand;
use App\Category;
use App\Cultivator;
use App\Filters\BatchFilters;
use App\Filters\BatchIntakeFilters;
use App\Filters\ReconcileLogFilters;
use App\Fund;
use App\Location;
use App\Repositories\DbSaleOrderRepository;
use App\Repositories\DbUserRepository;
use App\Role;
use App\TransferLog;
use App\User;
use Carbon\Carbon;
use Dompdf\Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;

class BatchesController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        view()->share('title', 'Inventory');
    }

    public function search_all()
    {
        $search = request()->get('q');
        $return_cost = request()->get('cost');

        $batches = Batch::select([
            'batches.category_id',
            'batches.brand_id',
            'batches.name',
            'batches.uom',
            DB::raw('MIN(batches.unit_price) as unit_price'),
            DB::raw('MIN(batches.suggested_unit_sale_price) as suggested_unit_sale_price'),
            DB::raw('MIN(batches.min_flex) as min_flex'),
            'brands.name as brand_name',
            'categories.name as cat_name',
        ])
            ->leftjoin('categories', 'batches.category_id', '=', 'categories.id')
            ->leftjoin('brands', 'batches.brand_id', '=', 'brands.id')
//            ->whereDate('batches.created_at','>','2023-01-01')
            ->where(function($q) use ($search) {
                $q->orWhere('batches.name', 'LIKE', '%'.$search.'%')
                    ->orWhere('brands.name', 'LIKE', '%'.$search.'%')
                    ->orWhere('categories.name', 'LIKE', '%'.$search.'%');
        })
            ->with([
            'category',
            'brand',
        ])
            ->groupBy([
                'batches.category_id',
                'batches.brand_id',
                'batches.name',
                'batches.uom',
                'brands.name',
                'categories.name'
            ])
            ->orderBy('batches.name')
            ->get();

//        dd($batches);
        $batch_results = collect();

        $batches->each(function ($batch, $key) use ($batch_results, $return_cost) {

            $data_item = [
                'id' => $batch->id,
                'sku' => $batch->ref_number,
                'category_id' => $batch->category_id,
                'category' => $batch->category->name,
                'orig_name' => $batch->name,
                'brand_id' => $batch->brand_id,
                'name' => $batch->category->name.': '.$batch->present()->branded_name,
                'sold_as_name' => $batch->name,
                'type' => $batch->type,
                'uom' => $batch->uom,
                'min_flex' => display_currency($batch->min_flex, 2, 0, ''),
                'min_flex_display' => display_currency($batch->min_flex),
                'suggested_unit_sale_price' => display_currency($batch->suggested_unit_sale_price, 2, 0, ''),
                'suggested_unit_sale_price_display' => display_currency($batch->suggested_unit_sale_price),
                'cog' => 1,
            ];

            if($return_cost) {
                $data_item['cost'] = display_currency($batch->getRawOriginal('unit_price')/100, 2, 0, '');
                $data_item['cost_display'] = display_currency($batch->getRawOriginal('unit_price')/100);
            }

            $batch_results->push($data_item);
        });
        //dd($batch_results);
        $t = [
            'status' => false,
            'error' => null,
            'count' => $batch_results->count(),
            'data' => [
                'batches' => $batch_results,
            ],
        ];

        return response()->json($t);
//        return true;
    }

//    public function search()
//    {
//        $search = request()->get('q');
//
//        $batches = Batch::currentInventory(null, [
//            'category',
//            'approved_inventory_by_location',
//            'sold_inventory_by_location',
//            'allocated_inventory',
//            'brand',
//        ])
//            ->search($search)
//            ->orderBy('category_id')
//            ->get();
//        //dd($batches);
//        $batch_results = collect();
//
//        $batches->each(function ($batch, $key) use ($batch_results) {
//
//            $data_item = [
//                'id' => $batch->id,
//                'sku' => $batch->ref_number,
//                'category_id' => $batch->category_id,
//                'category' => $batch->category->name,
//                'orig_name' => $batch->name,
//                'brand_id' => $batch->brand_id,
//                'name' => $batch->category->name.': '.$batch->present()->branded_name,
//                'sold_as_name' => $batch->name,
//                'type' => $batch->type,
//                'inventory' => $batch->available_for_sale,
//                'show_inventory' => true,
//                'uom' => $batch->uom,
//                'suggested_unit_sale_price' => display_currency($batch->suggested_unit_sale_price, 2, 0, ''),
//                'suggested_unit_sale_price_display' => display_currency($batch->suggested_unit_sale_price),
//                'cog' => 1,
//            ];
//
//            $batch_results->push($data_item);
//        });
//        //dd($batch_results);
//        $t = [
//            'status' => false,
//            'error' => null,
//            'data' => [
//                'batches' => $batch_results,
//            ],
//        ];
//
////        dd($t);
//
//        return response()->json($t);
//    }

    public function index(BatchFilters $batchFilters)
    {
        if (Gate::denies('batches.index')) {
            flash()->error('Access Denied');
            return redirect("/");
        }

        if (request()->get('reset')) {
            $batchFilters->resetFilters();
        }

        $categories = Category::active()->get();
        $vendors = (new DbUserRepository)->vendors()->orderBy('name')->get();
        $brands = Brand::orderBy('name')->get();

        $with = [
            'purchase_order.vendor',
            'category',
            'brand',
//            'locations_aggregate'
        ];

        $batches = Batch::currentInventory($batchFilters, $with)->get();
//dd($batches->first());
//        dd($batches->groupBy('location_name'));

        $inventory_intake = resolve('inventory_intake');
        $warnings = collect();
        if (Auth::user()->level() >= 60 && $inventory_intake) {
            $warning_msg = 'You have inventory that needs to be approved. <a href="'.route('batches.intake').'">Click Here</a>';
            $warnings->push($warning_msg);
        }

//        $location_inventory_value = Location::inventoryValueByLocation()->get();

        $filters = $batchFilters->getFilters()->toArray();

        return view('batches.index', compact(
            'batches',
            'filters',
            'vendors',
            'categories',
            'brands',
//            'funds',
//            'my_licenses',
//            'location_inventory_value',
            'warnings'
        ))
            ->with('locations', $this->locations);
    }

    public function create()
    {
        if (Gate::denies('batches.create')) {
            flash()->error('Access Denied');
            return back();
        }

//        if(!Auth::user()->hasLocation()) {
//            flash()->error('Can only create batch from a location!');
//            return redirect(route('batches.index'));
//        }

        $categories = Category::active()->get();
        $brands = Brand::active()->get();

        return view('batches.create', compact(
            'categories',
            'brands',
        ));
    }

    public function store(Request $request)
    {
        if (Gate::denies('batches.create')) {
            flash()->error('Access Denied');
            return back();
        }

        $batch = $request->get('_batches');

        $location_id = $batch['location_id']??Auth::user()->active_locations->first()->id;

        Batch::createBatch($batch, $location_id);

        return redirect(route('batches.index'));
    }

    public function sold(BatchFilters $batchFilters)
    {
        if (Gate::denies('batches.index')) {
            flash()->error('Access Denied');

            return back();
        }

        view()->share('title', 'Archived Batches');

        $categories = Category::active()->get();
        $vendors = (new DbUserRepository)->vendors()->orderBy('name')->get();

        $batches = Batch::soldInventory($batchFilters, [
            'purchase_order.vendor',
            'category',
//            'allocated_inventory',
//            'approved_inventory_by_location',
//            'sold_inventory_by_location',
            'brand',
        ])->get();

        $filters = $batchFilters->getFilters()->toArray();

        return view('batches.sold', compact(
            'batches',
            'filters',
            'vendors',
            'categories'
        ));
    }

    public function show(Batch $batch, User $selected_customer)
    {
        if (Gate::denies('batches.show')) {
            flash()->error('Access Denied');
            return back();
        }

        $batch->load([
            'category',
            'purchase_order',
            'order_details',
            'reconciled_inventory',
            'allocated_inventory.batch_location.location',
            'allocated_inventory.batch_location.parent_batch_location.location',
            'allocated_inventory.batch_location.child_batch_location.location',
            'allocated_inventory.batch_location.intake_activity.causer',
        ]);

        $sales_reps = User::salesReps()->orderBy('name')->get();

        $customers = User::customers()->orderBy('name')->get();
        $customer_role_id = Role::whereName('customer')->first()->id;

        $all_sale_orders = (new DbSaleOrderRepository)->ordersByBatchId($batch->id);

        return view('batches.show', compact(
            'batch',
            'customers',
            'selected_customer',
            'sales_reps',
            'all_sale_orders',
            'customer_role_id'
        ));
    }

    public function edit(Batch $batch)
    {
        if (Gate::denies('batches.edit')) {
            flash()->error('Access Denied');

            return back();
        }
        $brands = Brand::active()->orderBy('name')->get();
        $categories = Category::active()->get();


        //dd($testing_laboratory);
        return view('batches.edit', compact('batch', 'categories', 'brands'));
    }

    public function update(Request $request, Batch $batch)
    {
        if (Gate::denies('batches.edit')) {
            flash()->error('Access Denied');

            return back();
        }

        $data = request()->all();
        //dd($data);
        $batch_original = $batch->getOriginal();

        if ($data['status'] == 'Lab') {
            $data['testing_status'] = 'In-Testing';
        }

        if (! empty($data['testing_status']) && in_array($data['testing_status'], ['Passed', 'Failed'])) {
            $data['status'] = 'Inventory';
        }

        try {
            $data['ref_number'] = str_replace('/', '-', $data['ref_number']);

            $batch->update($data);

            $activity_prop = collect();

            if ($changes = $batch->getChanges()) {
                foreach ($changes as $field => $new_value) {
                    if ($field == 'updated_at') {
                        continue;
                    }

                    $old_value = $batch_original[$field];
                    $new_value = $new_value;

                    switch ($field) {
                        case 'suggested_unit_sale_price':
                        case 'min_flex':
                            $old_value = display_currency($batch_original[$field]);
                            $new_value = display_currency($new_value / 100);
                            break;
                        case 'ref_number':
                            $field = 'SKU';
                            break;
                    }
                    $activity_prop->put(clean_field_label($field), $old_value.' -> '.$new_value);
//                    $activity_prop->put('New '.$field, );
                }
            }

            activity('batch')
                ->causedBy(Auth::user())
                ->performedOn($batch)
                ->withProperties($activity_prop)
                ->log('Updated');
        } catch (QueryException $e) {
            flash()->error($e->errorInfo[2]);

            return back()->withInput();
        } catch (\Exception $e) {
            flash()->error($e->getMessage());

            return back()->withInput();
        }

        flash()->success('Successfully updated '.$batch->name);

        return redirect(route('batches.show', $batch->id));
    }

    public function intake(Request $request, BatchIntakeFilters $batchIntakeFilters)
    {
        view()->share('title', 'Batch Intakes');

        if (request()->get('reset')) {
            $batchIntakeFilters->resetFilters();

            return redirect(route('batches.intake'));
        }

        $categories = Category::active()->get();

        $intake_batch_locations = BatchLocation::needIntakeApproval($batchIntakeFilters)->get();

        $filters = $batchIntakeFilters->getFilters()->toArray();

        return view('batches.intake', compact(
            'filters',
            'categories',
            'intake_batch_locations'
        ));
    }
    public function transfer(Request $request, Batch $batch)
    {
        view()->share('title', 'Batch Transfer');

//        if( ! $batch->canTransfer()) {
//            flash()->error('Can only transfer bulk flower');
//            return redirect(route('batches.index'));
//        }

        $categories = Category::active()->get();
        $brands = Brand::orderBy('name')->get();
        $funds = Fund::pluck('name', 'id');

        if ($request->isMethod('post')) {
            $packages_created = [];
            for ($i = 0; $i < count($request->all()['rows']['ref_number']); $i++) {
                foreach ($request->all()['rows'] as $field => $vals) {
                    $packages_created[$i][$field] = $vals[$i];
                }
            }

            $qty_to_xfer = $request->get('transfer_qty', 0);
            $packer_name = $request->get('packer_name');
            $start_weight = $request->get('start_weight', 0);
            $used_weight = $request->get('used_weight', 0);
            $used_weight_uom = $request->get('used_weight_uom', 0);
            $remaining_weight = $request->get('remaining_weight', 0);
            $product_name = $request->get('name');
//            $packages_created = $request->get('rows');

            if (! $used_weight) {
                $used_weight = ($start_weight - $remaining_weight);
            }

            if (empty($qty_to_xfer)) {
                if ($batch->uom == 'lb' && $used_weight_uom == 'g') {
                    $qty_to_xfer = $used_weight / config('inventorymgmt.uom.lb');
                } elseif ($batch->uom == 'g' && $used_weight_uom == 'lb') {
                    $qty_to_xfer = $used_weight * config('inventorymgmt.uom.lb');
                    $used_weight = $qty_to_xfer;
                } elseif ($batch->uom == 'lb' && $used_weight_uom == 'lb') {
                    $qty_to_xfer = $used_weight;
                    $used_weight = $used_weight * config('inventorymgmt.uom.lb');
                } else {
                    $qty_to_xfer = $used_weight;
                }
            }

//            dump('qty to xfer');
//            dump($qty_to_xfer);
//
//            dump('start weight');
//            dump($start_weight);
//
//            dump('remaining weight');
//            dump($remaining_weight);
//
//            dump('used weight');
//            dump($used_weight);
//
            $available_inv = ($batch->wt_grams ?: $batch->inventory);
//
//            dump('available inv:');
//            dump($available_inv);
//
//            dd(bccomp($qty_to_xfer, $available_inv, 4));

            if (! $qty_to_xfer || bccomp($qty_to_xfer, $available_inv, 4) > 0) {
                flash()->error('Convert quantity cannot exceed available quantity'.$qty_to_xfer.'--'.$available_inv);

                return redirect(route('batches.transfer', $batch->id))
                    ->withInput($request->all());
            } else {

//dump($used_weight);
                //dump($qty_to_xfer);
                //dump($packages_created);
                //dump($product_name);
                //dd('end');
                try {
                    $batch->transfer(
                        $used_weight,
                        $qty_to_xfer,
                        $packages_created,
                        $packer_name,
                        $product_name
                    );

                    if ($batch->wt_grams) {
                        $batch->wt_grams = (float) bcsub($batch->wt_grams, $qty_to_xfer, 4);
                        $batch->unit_price = (float) bcsub($batch->unit_price, $batch->total_converted_cost, 4);

                        if ($batch->wt_grams <= 0) {
                            $batch->inventory = 0;
                        }
                        if ($batch->unit_price < 0) { //
//                        (new TransferLog)->storePackagingLoss($batch);
                            $batch->unit_price = 0;
                        }
//                    dd($batch);
                    } else {
                        $batch->inventory = (float) bcsub($batch->inventory, $qty_to_xfer, 4);
//                    $batch->transfer = (float)bcadd($batch->transfer, $qty_to_xfer, 4);
                    }

                    $batch->save();

                    return redirect(route('batches.transfer-log', $batch->id));
                } catch (\Exception $e) {
                    DB::rollBack();
//                    dd($e);
                    flash()->error($e->getMessage());

                    return redirect()->back();
                }
            }
        }

        return view('batches.transfer', compact('batch', 'categories', 'brands', 'funds'));
    }

    public function transfer_log(Batch $batch, TransferLog $transferLog)
    {
        view()->share('title', 'Batch Transfer Log');

        $batch->load(['transfer_logs' => function ($query) {
            $query->where('type', 'Pre-Pack')->orderBy('created_at', 'desc')
                ->with(['transfer_log_details.batch_created', 'user', 'batch_converted']);
        }]);

        if (request()->isMethod('post')) {
            try {
                $exitCode = Artisan::call('fix:reverse_prepack', [
                    'transfer_log_id' => $transferLog->id,
                    '--no-interaction' => true,
                ]);

//            dd($exitCode);
//            dd(Artisan::output());
//
            } catch (\Exception $e) {
                flash()->error($e->getMessage());

                return redirect(route('batches.transfer-log', $batch->id));
            }

            return redirect(route('batches.transfer-log', $batch->id));
        }

        return view('batches.transfer-log', compact('batch', 'transferLog'));
    }

    public function pickup(Batch $batch)
    {
        if (Gate::denies('batches.pickup')) {
            flash()->error('Access Denied');

            return back();
        }

        if (bccomp(request('pickup_qty'), $batch->inventory, 2) === 1) {
            flash()->error('Not allowed to pickup more than: '.$batch->inventory);

            return redirect(route('batches.show', $batch->id));
        }

        $batch->pickup(request('pickup_qty'));

        return redirect(route('batches.show', $batch->id));
    }

    public function sell(Request $request, Batch $batch)
    {
        if (Gate::denies('batches.sell')) {
            flash()->error('Access Denied');

            return back();
        }

        if (! Auth::user()->hasLocation()) {
            flash()->error('No Store Location!');

            return back();
        }
        //dd($request->all());
        $vault_log_ref = Session::get('vault_log_ref');

        $customer = User::find(request('customer_id', request('destination_license_id')));

        $location_id = Auth::user()->current_location->id;
        $customer_id = ($customer ? $customer->id : 0);
        $bill_to_id = (request('bill_to_id') ? request('bill_to_id') : null);
        $sales_rep_id = request('sales_rep_id');
//        $broker_id = request('broker_id');
        $sale_order_id = request('sale_order_id');
        $destination_license_id = request('destination_license_id');

        $txn_date = request('txn_date');
        $expected_delivery_date = request('expected_delivery_date', Carbon::now());
        $terms = request('terms');
        $customer_type = request('customer_type');
        $add_sample = request('add_sample');
        $sale_type = request('sale_type');
        $notes = request('notes');

        try {
            if (! $customer_id && ! $sale_order_id) {
                throw new Exception('Please select a customer or sale order.');
            }

            $quantity = request('sell_units');

            if ($quantity <= 0 || bccomp($quantity, $batch->available_for_sale) === 1) {
                throw new Exception('Quantity to sell exceeds available: '.$batch->available_for_sale.' '.$batch->uom);
            }

            DB::beginTransaction();

            $saleOrder = new DbSaleOrderRepository();

            if ($customer) {
                $sale_order = $saleOrder->create(compact('customer', 'txn_date', 'expected_delivery_date',
                    'customer_type', 'sales_rep_id', 'bill_to_id', 'sale_type', 'terms', 'destination_license_id', 'notes', 'location_id'));

                activity('sale-order')
                    ->causedBy(Auth::user())
                    ->performedOn($sale_order)
                    ->log('Created');
            } else {
                $sale_order = $saleOrder->find($sale_order_id);
            }

            $redirect_vault_log = false;

            $sale_price = $request->get('pre_tax_sale_price');

            $sale_order->addUpdateItem($batch, request('sold_as_name'), $quantity, $sale_price);

            if ($vault_log_ref) {
                $redirect_vault_log = true;
                $vault_log_ref->order_detail_id = $sale_order->latest_order_detail->id;
                $vault_log_ref->save();
                Session::forget('vault_log_ref');
            }

            $sale_order->calculateTotals();

            DB::commit();

            flash()->success($batch->name.' added to sale order');
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 22003) {
                flash()->error('Unable to add item to order. Review UOM and sale price.');
            } else {
                flash()->error($e->getMessage());
            }

            return redirect(route('batches.show', $batch->id));
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());
            if ($destination_license_id) {
                return redirect(route('batches.show.customer', [$batch->id, $destination_license_id]));
            } else {
                return redirect(route('batches.show', $batch->id));
            }
        }

        if ($redirect_vault_log) {
            return redirect(route('vault-logs.index'));
        } else {
            return redirect(route('sale-orders.show', $sale_order->id));
        }
    }

    public function release(Batch $batch)
    {
        if (Gate::denies('batches.release')) {
            flash()->error('Access Denied');

            return back();
        }

        $quantity = request('release_units');
        $batchPickup = $batch->myPickupInTransit;

        if ($quantity > $batchPickup->units) {
            flash()->error('Can not return this many.');

            return redirect(route('batches.show', $batch->id));
        }

        $batchPickup->release($quantity);
        $batch->release($quantity);

        return redirect(route('batches.show', $batch->id));
    }

    public function sales(Batch $batch)
    {
        $title = 'Sale Orders';

        $sale_orders = (new DbSaleOrderRepository)->salesByBatchId($batch->id);
        //dd($sale_orders);
        return view('batches.sales', compact('sale_orders', 'title', 'batch'));
    }


    public function resetFilters(BatchFilters $batchFilters)
    {
        $batchFilters->resetFilters();

        return redirect(route('batches.index'));
    }

    public function resetReconcileLogFilters(ReconcileLogFilters $reconcileLogFilters)
    {
        $reconcileLogFilters->resetFilters();

        return redirect(route('batches.reconcile-log'));
    }

    public function reconcile(Batch $batch)
    {
        if (Gate::denies('batches.reconcile')) {
            flash()->error('Permission Denied!');
            if($batch->exists) {
                $redir = route('batches.show', $batch);
            } else {
                $redir = route('batches.index');
            }
            return redirect($redir);
        }

        view()->share('title', 'Inventory / Reconcile');

        $target_batch_id = $batch->id;

        $batch_query = Batch::allInventory(null, [
            'category',
            'brand',
//            'allocated_inventory',
            'purchase_order.vendor',
        ]);

//dd($batch_query->get());
        if($batch->id) {
            $batch_query->where('batches.id', $batch->id);
        }

        if(request('location')) {
            $batch_query->where('locations.id', request('location'));
        }


        $batches = $batch_query->get();

        return view('batches.reconcile', compact('batches', 'target_batch_id'));
    }

    public function reconcileProcess(Request $request)
    {
        if (Gate::denies('batches.reconcile')) {
            flash()->error('Permission Denied!');

            return redirect(route('dashboard'));
        }
//dd($request->all());
        try {

            DB::beginTransaction();

//            if ($request->has('adjustment_file')) {
//                //get uploaded file packages
//                $path = $request->file('adjustment_file')->getRealPath();
//                $adjustment_file = collect(array_map('str_getcsv', file($path)));
//
//                foreach ($adjustment_file as $batch_adjustment) {
//                    dd($batch_adjustment);
//                    $batch = Batch::where('ref_number', $batch_adjustment[0])->first();
//                    if (empty($batch) || $batch->inventory <= 0) {
//                        continue;
//                    }
//
//                    $batch_inventory = ($batch->wt_based ? $batch->wt_grams : $batch->inventory);
//
////                    $batch_available = (Auth::user()->hasLocation()?$batch->available_for_sale:$batch->available_for_allocation);
//
//                    if ($batch->wt_based) {
//                        $new_value = bcadd($batch->wt_grams, (float) $batch_adjustment[1], 4);
//                    } else {
//                        $new_value = bcadd($batch->inventory, (float) $batch_adjustment[1], 4);
//                    }
//
//                    $batch->reconcile($new_value, $batch_inventory,  $batch_adjustment[2], null);
//                }
//            }

            if ($request->has('batch')) {

                $succ_messages = collect();
                $err_messages = collect();

                $batch_id = $request->get('batch_id', null);
                $redir_to_batch = ($batch_id ? true : false);

                foreach ($request->get('batch') as $batch_id => $batch_values) {

                    $batch = Batch::find($batch_id);

                    foreach($batch_values as $location_id => $batch_value) {

                        $batch_location_inventory = $batch->unitCostAtLocation($location_id);

                        //new value - pending
                        $reconcile_to = (float)bcsub($batch_value['new_value'], $batch_location_inventory->pending_inventory, 4);

                        if ($reconcile_to < 0) {
                            throw new \Exception('Cannot reconcile to less than is available: '.$batch_location_inventory->pending_inventory.' '.$batch->uom);
                        }

                        $reconcile_success = $batch->reconcile(
                            $reconcile_to,
                            $batch_location_inventory->available_inventory,
                            $location_id,
                            ($batch_location_inventory->location_unit_price?:$batch->unit_price),
                            $batch_value['reason'],
                            $batch_value['notes']
                        );

                        if($reconcile_success) {
                            $succ_messages->push($batch->ref_number.' - '.$batch->name.' reconciled at location: '.$batch_value['location_name']);
                        }

                    }

                }
            }

            if ($succ_messages->count()) {
                flash()->success($succ_messages->implode('<br>'));
            }

            if ($err_messages->count()) {
                flash()->error($err_messages->implode('<br>'));
            }

            DB::commit();

            return redirect(route('batches.reconcile-list', ($redir_to_batch ? $batch_id : null)));
        } catch (\Exception $e) {
            DB::rollBack();

//            Bugsnag::notifyException($e);

            flash()->error('Error: '.$e->getMessage());

            return redirect(route('batches.reconcile-list', ($redir_to_batch ? $batch_id : null)));
        }
    }

    public function reconcileLog(Request $request, ReconcileLogFilters $reconcileLogFilters, Batch $batch)
    {
        if (Gate::denies('batches.reconcile')) {
            flash()->error('Permission Denied!');

            return redirect(route('dashboard'));
        }

        if (request()->get('reset')) {
            $reconcileLogFilters->resetFilters();
        }

        view()->share('title', 'Inventory / Reconciliation Log');

        $date_presets = date_presets();
        $categories = Category::active()->get();
        $vendors = (new DbUserRepository)->vendors(null)->get();
        $brands = Brand::orderBy('name')->get();

        $with =[
            'user',
            'location',
            'batch_converted.locations_aggregate',
            'batch_converted.purchase_order.vendor',
            'batch_converted.category',
        ];

        $Qbuilder = TransferLog::reconciliationLog($reconcileLogFilters, $with, $batch);

        $reconcile_logs = $Qbuilder->paginate(50);

        $total_recon_amount = ($Qbuilder->sum('inventory_loss') / 100) * -1;

        $filters = $reconcileLogFilters->getFilters()->toArray();
//debug($filters);
        return view('batches.reconcile-log', compact(
            'date_presets',
            'filters',
            'categories',
            'vendors',
            'brands',
            'reconcile_logs',
            'total_recon_amount'
        ));
    }

    public function allocate(Request $request, Batch $batch, Location $location)
    {
        if (Auth::user()->level() < 60) {
            flash()->error('Access Denied');
            return redirect(route('batches.show', $batch));
        }

        view()->share('title', 'Inventory / Allocate');

        $locations = Auth::user()->active_locations->filter(function($active_location) use ($location) {
            return $location->id != $active_location->id;
        });

        if( ! $locations->count()) {
            flash()->error('No active locations to allocate to.');
            return redirect(route('batches.show', $batch));
        }

        $allocate_from_location = $batch->locations_aggregate->firstWhere('id', $location->id);
        $existing_allocated_locations = $batch->locations_aggregate->filter(function($v, $k) use ($location) {
            return $v->id != $location->id;
        })->keyBy('id');

        return view('batches.allocate', compact(
            'batch',
            'locations',
            'allocate_from_location',
            'existing_allocated_locations'
        ));
    }

    public function allocateStore(Request $request, Batch $batch, Location $location)
    {
        if (Auth::user()->level() < 60) {
            flash()->error('Access Denied');

            return back();
        }

        try {

            $allocate_from_location_inventory = $batch->unitCostAtLocation($location->id);

            $locations = collect($request->get('locations'));

            $avail_to_allocate = $allocate_from_location_inventory->available_inventory;

            if (bccomp($locations->sum('quantity'), $avail_to_allocate, 4) > 0) {
                throw new \Exception('Can not allocate more than available inventory: '.$avail_to_allocate.' '.$batch->uom);
            }

            //remove locations that have null quantity
            $new_locations = $locations->reject(function ($value, $key) {
                return is_null($value['quantity']);
            });

            DB::beginTransaction();

            $new_locations->each(function ($batch_location, $allocate_to_location_id) use ($batch, $location, $allocate_from_location_inventory) {

                if($batch_location['quantity'] < 0) {
                    throw new \Exception('Unable to pull inventory back from a location. Inventory allocation must be initiated from the source location.');
                }

                $batch->allocate(
                    $location->id,
                    $allocate_to_location_id,
                    $batch_location['quantity'],
                    $batch_location['name'],
                    $allocate_from_location_inventory->location_unit_price,
                    $batch_location['suggested_unit_sale_price'],
                    $batch_location['min_flex']
                );
            });

            DB::commit();

            flash()->success('Batch Allocated');

            return redirect(route('batches.show', $batch->id));
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());

            return back();
        }
    }

    public function activityLog(Request $request, Batch $model)
    {
        view()->share('title', 'Batch / Activity Log');

        $model->load('activity_logs.causer');

        $heading3 = $model->category->name.': '.$model->present()->branded_name.' <br> '.$model->ref_number;

        $back_link = route('batches.show', $model->id);

        return view('activity-log', compact('model', 'heading3', 'back_link'));
    }
}
