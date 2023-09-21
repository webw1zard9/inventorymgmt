<?php

namespace App\Http\Controllers;

use App\Batch;
use App\Brand;
use App\Category;
use App\Filters\PurchaseOrderFilters;
use App\Fund;
use App\License;
use App\Location;
use App\Order;
use App\OrderDetail;
use App\PurchaseOrder;
use App\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use App\Repositories\DbUserRepository;
use App\ReturnOrder;
use App\Role;
use App\TaxRate;
use App\TransferLog;
use App\User;
use App\ReturnPurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Dompdf\Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Scottlaurent\Accounting\Models\JournalTransaction;

class PurchaseOrdersController extends Controller
{
    protected $purchase_order;

    public function __construct(PurchaseOrderRepositoryInterface $purchaseOrderRepositoryInterface)
    {
        parent::__construct();

        $this->purchase_order = $purchaseOrderRepositoryInterface;
    }

    public function index(PurchaseOrderFilters $purchaseOrderFilters)
    {
        if (Gate::denies('po.index')) {
            flash()->error('Access Denied');

            return back();
        }

        $purchase_orders = PurchaseOrder::with([
            'batches',
            'fund',
            'location',
            'journal'
        ])
            ->filters($purchaseOrderFilters)
            ->orderBy('id', 'desc')
            ->withTrashed()
            ->paginate(25);

        $filters = $purchaseOrderFilters->getFilters()->toArray();
        $funds = Fund::pluck('name', 'id');

        $vendors = (new DbUserRepository)->vendors()->get();

        return view('purchase_orders.index', compact('purchase_orders', 'vendors', 'filters', 'funds'));
    }

    public function create(Request $request, User $vendor)
    {
        if (Gate::denies('po.create')) {
            flash()->error('Access Denied');

            return back();
        }

        $segment_name = $request->segment(2);
        $vendor_role_id = Role::where('name', 'vendor')->first()->id;

        $categories = Category::active()->get();
        $brands = Brand::active()->get();
        $vendors = User::vendors()->orderBy('name')->where('active', 1)->pluck('name', 'id');
        $funds = Fund::pluck('name', 'id');
        $locations = Auth::user()->active_locations;
//dd($locations);
        //dd($vendors);
        $destination_licenses = License::system_licenses()->get();

        //dd($destination_licenses);
        return view('purchase_orders.create', compact(
            'vendors',
            'vendor',
            'categories',
            'brands',
            'funds',
            'locations',
            'destination_licenses',
            'segment_name',
            'vendor_role_id'));
    }

    public function store(Request $request)
    {
        $data = $request->all();

        try {
            if ($path = $request->file('_packages')) { //process file

                $packages = collect(array_map('str_getcsv', file($path->getRealPath())));

                //remove header
                $packages->pull(0);

                //get category & brand ids
                $packages->transform(function ($row) {
                    $category = Category::select('id')->where('name', $row[0])->first();
                    if ($category) {
                        $row['category_id'] = $category->id;
                    }

                    $brand = Brand::select('id')->where('name', $row[2])->first();
                    if ($row[2] && is_null($brand)) {
                        throw new Exception('Brand Name: '.$row[2].' - Invalid');
                    }
                    if ($brand) {
                        $row['brand_id'] = $brand->id;
                    }

                    $row['total_cost'] = ($row[5] * $row[7]);

                    return $row;
                });

//                dd($packages);

//                $data['_batches'] = [
//                    'ref_number' => $packages->pluck(1)->toArray(),
//                    'category_id' => $packages->pluck('category_id')->toArray(),
//                    'brand_id' => $packages->pluck('brand_id')->toArray(),
//                    'name' => $packages->pluck(3)->toArray(),
//                    'type' => $packages->pluck(4)->toArray(),
//                    'quantity' => $packages->pluck(5)->toArray(),
//                    'uom' => $packages->pluck(6)->toArray(),
//                    'unit_cost' => $packages->pluck(7)->toArray(),
//                    'total_cost' => $packages->pluck('total_cost')->toArray(),
//                    'suggested_unit_sale_price' => $packages->pluck(8)->toArray(),
//                    'min_flex' => $packages->pluck(9)->toArray(),
//                ];
            }

            DB::beginTransaction();

            $po = $this->purchase_order->create($data);

            DB::commit();

            flash()->success('Purchase Order #'.$po->ref_number.' Created');

            return redirect(route('purchase-orders.show', $po->id));
        } catch (\Exception $e) {
            DB::rollBack();
            $err_msg = (! empty($e->errorInfo[2]) ? $e->errorInfo[2] : $e->getMessage());
            flash()->error($err_msg);

            return back()->withInput($request->all());
        }
    }
    public function show(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (Gate::denies('po.show')) {
            flash()->error('Access Denied');
            return back();
        }

        view()->share('title', 'Purchase Order');

        $location_id = $request->get('lid', Auth::user()->current_location->id);
        $payment_amount = $request->get('a');
        $categories = Category::active()->get();
        $locations = Location::pluck('name', 'id')->union($purchaseOrder->location()->pluck('name', 'id'));
        $brands = Brand::active()->get();

        if ($request->isMethod('post')) {
            try {
                $batch = null;
                foreach (request()->get('_batches') as $field => $vals) {
                    $batch[$field] = $vals[0];
                }

                DB::beginTransaction();

                $added_batch = $purchaseOrder->addBatch($batch);

                $purchaseOrder->refresh();
                $purchaseOrder->updateTotals();

                $purchaseOrder->journal->resetCurrentBalances();

                DB::commit();

                flash()->success('Batch added!');
            } catch (\Exception $e) {
                DB::rollBack();
                flash()->error($e->getMessage());

                return redirect(route('purchase-orders.show', $purchaseOrder->id))
                    ->withInput($request->all());
            }
        }

        $purchaseOrder->load([
            'vendor',
            'originating_entity',
            'origin_license',
            'batches.children_batches.order_details',
            'batches.category',
            'batches.order_details.sale_order.location',
            'batches.locations',
            'batches.allocated_inventory',
            'batches.allocated_and_sold_inventory',
            'batches.transfer_logs',
            'batches.brand',
            'fund',
            'destination_license.license_type',
            'transactions' => function ($q) {
                $q->orderBy('txn_date');
            },
            'transactions.parent',
            'transactions.location',
            'transactions.user',
            'return_purchase_orders'=>function($q) {
                $q->orderBy('id', 'desc');
            },
            'return_purchase_orders.order_details.location',
            'return_purchase_orders.order_details.batch'
        ]);
//dd($purchaseOrder);
        $purchaseOrder->loadLocationBalances();

        return view('purchase_orders.show', compact(
            'purchaseOrder',
            'categories',
            'brands',
            'locations',
            'location_id',
            'payment_amount'
        ));
    }

    public function returnItems(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (Gate::denies('po.show')) {
            flash()->error('Access Denied');

            return back();
        }

        view()->share('title', 'Purchase Order / Return Items');

        $purchaseOrder->load([
            'vendor',
            'originating_entity',
            'origin_license',
            'batches.children_batches.order_details',
            'batches.category',
            'batches.order_details.sale_order.location',
            'batches.locations',
            'batches.allocated_inventory',
            'batches.allocated_and_sold_inventory',
            'batches.transfer_logs',
            'destination_license.license_type',
        ]);

        $purchaseOrder->loadLocationBalances();
//        dd($purchaseOrder);
        //dd($purchaseOrder->batches);
        return view('purchase_orders.return_items', compact(
            'purchaseOrder',

        ));
    }

    public function returnItemsStore(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (Gate::denies('po.show')) {
            flash()->error('Access Denied');
            return back();
        }

        try {

            DB::beginTransaction();

            $return_order_details = collect();

            foreach($request->get('batches') as $batch_id => $locations) {

                $batch = Batch::findOrFail($batch_id);

                foreach($locations['locations'] as $location_id => $return_data) {

                    if(empty($return_data['quantity']) || is_null($return_data['quantity'])) continue;

//                    if( ! $location_id) { // nest
//
//                        if($return_data['quantity'] > $batch->available_for_allocation) {
//                            throw new \Exception("Return quantity exceeds available to return. Try again.");
//                        }
//
//                        if($return_data['unit_cost'] != $batch->unit_price) {
//                            throw new \Exception("Unit cost has changed. Try again.");
//                        }
//
//                        $unit_cost = $batch->unit_price;
//
//                        $batch->units_purchased = bcsub($batch->units_purchased, $return_data['quantity'],4);
//                        $batch->inventory = bcsub($batch->inventory, $return_data['quantity'],4);
//
//                        $avg_unit_cost = $batch->average_unit_cost();
//                        $batch->avg_unit_price = $avg_unit_cost;
//                        $batch->subtotal_price = ($avg_unit_cost * $batch->units_purchased);
//                        $batch->save();
//
//                    } else { //locations

                        if($return_data['quantity'] > $batch->allocated_and_sold_inventory->groupBy('id')[$location_id]->sum('batch_location.quantity')) {
                            throw new \Exception("Return quantity exceeds available to return. Try again.");
                        }

                        if($return_data['unit_cost'] != $batch->cost_by_location[$location_id]) {
                            throw new \Exception("Unit cost has changed. Try again.");
                        }

                        $unit_cost = $batch->cost_by_location[$location_id];

                        $batch->locations()->attach($location_id, [
                            'quantity'=>($return_data['quantity'] * -1),
                            'unit_price'=>$unit_cost,
                            'suggested_unit_sale_price'=>0,
                            'return_item'=>1,
                            'approved'=>1,
                            'approved_at'=>Carbon::now(),
                        ]);

                        $avg_unit_cost = $batch->average_unit_cost();
//                        dd($avg_unit_cost);

                        $batch->avg_unit_price = $avg_unit_cost;

                        $batch->units_purchased = bcsub($batch->units_purchased, $return_data['quantity'],4);
                        $batch->inventory = bcsub($batch->inventory, $return_data['quantity'],4);
                        $batch->subtotal_price = ($avg_unit_cost * $batch->units_purchased);
                        $batch->save();
//                    }


                    $return_order_details->push(new OrderDetail([
                        'batch_id'=>$batch_id,
                        'location_id'=>($location_id?:null),
                        'sold_as_name'=>$batch->name,
                        'units'=>$return_data['quantity'],
                        'units_fulfilled'=>$return_data['quantity'],
                        'units_accepted'=>$return_data['quantity'],
                        'unit_cost'=>$unit_cost,
                        'unit_sale_price'=>0,
                        'cog'=>1,
                        '_subtotal'=>($return_data['quantity'] * $unit_cost),
                    ]));

                }
            }

            //create return order
            $return_purchase_order = ReturnPurchaseOrder::create([
                'parent_id'=>$purchaseOrder->id,
                'user_id'=>Auth::user()->id,
                'vendor_id'=>$purchaseOrder->vendor_id,
                'txn_date'=>Carbon::now(),
                'type'=>'return',
                'status'=>'closed',
                'subtotal'=>$return_order_details->sum('line_cost'),
                'total'=>$return_order_details->sum('line_cost'),
                'balance'=>0,
            ]);

            $return_purchase_order->set_order_id();
            $return_purchase_order->order_details()->saveMany($return_order_details);

            $purchaseOrder->updateTotals();

            $activity_prop = collect([
                'Return#' => $return_purchase_order->ref_number
            ]);

            activity('purchase-order')
                ->causedBy(Auth::user())
                ->performedOn($purchaseOrder)
                ->withProperties($activity_prop)
                ->log('Items Returned');

            DB::commit();

            flash()->success('Return Order Created!');

            return redirect(route('purchase-orders.show', $purchaseOrder->id));

        } catch(\Exception $e) {

            DB::rollBack();
            flash()->error($e->getMessage());

            return back()->withInput($request->all());

        }

    }

    public function remove(PurchaseOrder $purchaseOrder)
    {
        try {
            DB::beginTransaction();

            if ($purchaseOrder->batches->count()) {
                $purchaseOrder->batches->each(function ($batch) {
                    if (bccomp($batch->units_purchased, $batch->inventory, 4) !== 0) {
                        throw new \Exception('Error - '.$batch->name.' '.$batch->ref_number.' has activity. Unable to delete.');
                    }
                    $batch->delete();
                });
            }

            $purchaseOrder->status = 'voided';
            $purchaseOrder->save();

            $purchaseOrder->delete();

            $purchaseOrder->refresh();
            $purchaseOrder->updateTotals();

            activity('purchase-order')
                ->causedBy(Auth::user())
                ->performedOn($purchaseOrder)
                ->log('Voided');

            DB::commit();

            return redirect(route('purchase-orders.show', $purchaseOrder->id));
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());

            return redirect(route('purchase-orders.show', $purchaseOrder));
        }
    }

    public function removeAllItems(PurchaseOrder $purchaseOrder)
    {
        try {
            DB::beginTransaction();

            if ($purchaseOrder->batches->count()) {
                $purchaseOrder->batches->each(function ($batch) {
                    if (bccomp($batch->units_purchased, $batch->inventory, 4) !== 0) {
                        throw new \Exception('Error - '.$batch->name.' '.$batch->ref_number.' has activity. Unable to delete.');
                    }
                    $batch->delete();
                });
            }

            $purchaseOrder->refresh();
            $purchaseOrder->updateTotals();

            DB::commit();

            return redirect(route('purchase-orders.show', $purchaseOrder->id));
        } catch (\Exception $e) {
            DB::rollBack();

            flash()->error($e->getMessage());

            return redirect(route('purchase-orders.show', $purchaseOrder));
        }
    }

    public function printPo(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'transactions' => function ($q) {
                $q->orderBy('txn_date');
            },
        ]);

        $pdf = PDF::loadView('purchase_orders.print_po', compact('purchaseOrder'));

//        return view('purchase_orders.print_po', compact('purchaseOrder'));

        return $pdf->download(\Str::slug($purchaseOrder->vendor->name).'-'.$purchaseOrder->ref_number.'.pdf');
    }


    public function processUpload(Request $request)
    {
        $categories = Category::active()->get();
        $funds = Fund::pluck('name', 'id');

        $vendor = User::where('id', $request->get('vendor_id'))->first();
        $origin_license = License::with('license_type')->find($request->get('origin_license_id'));
        $destination_license = License::with('license_type')->find($request->get('destination_license_id'));

        $customer_type = $request->get('customer_type');
        $txn_date = Carbon::parse($request->get('txn_date'));
        $terms = $request->get('terms');
        $fund = Fund::find($request->get('fund_id'));
        $manifest_no = $request->get('manifest_no');

        //get uploaded file packages
        $path = $request->file('packages')->getRealPath();

        $packages = collect(array_map('str_getcsv', file($path)));

        return view('purchase_orders.review-upload', compact(
            'categories',
            'funds',
            'vendor',
            'origin_license',
            'destination_license',
            'customer_type',
            'txn_date',
            'terms',
            'fund',
            'packages',
            'manifest_no'
        ));
    }

    public function retag(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($request->isMethod('post')) {
            $tag_id = $request->get('tag_id');
            $produce_lbs = $request->get('create_pounds');

//        dump($tag_id);

            foreach ($purchaseOrder->batches as $batch) {
                if ($batch->units_purchased != $batch->inventory) {
                    continue;
                }
                if (! in_array($batch->uom, ['lb', 'g'])) {
                    continue;
                }

                $uid = config('inventorymgmt.metrc_tag')[$purchaseOrder->destination_license_id].str_pad((int) $tag_id, 9, 0, STR_PAD_LEFT);

                $used_weight = ($batch->uom == 'g') ? $batch->inventory : $batch->inventory * config('inventorymgmt.uom.lb');
//            $qty_to_xfer = ($batch->uom == 'lb') ? $batch->inventory : $batch->inventory / config('inventorymgmt.uom.lb');
                $qty_to_xfer = $batch->inventory;

//                $transfer_log_data = [
//                    'user_id' => Auth::user()->id,
//                    'batch_id' => $batch->id,
//                    'quantity_transferred' => $qty_to_xfer,
//                    'start_wt_grams' => $used_weight,
//                    'packer_name'=>'System',
//                ];
//
                ////            dd($transfer_log_data);
//
//                $transfer_log = new TransferLog($transfer_log_data);

//            dump($transfer_log);

                //amount
                $amount = $batch->inventory;
                $uom = $batch->uom;
                if ($produce_lbs && $batch->uom == 'g') {
                    $amount = $batch->inventory / config('inventorymgmt.uom.lb');
                    $uom = 'lb';
                }

                $packages_created = [
                    [
                        'ref_number' => $uid,
                        'category_id' => $batch->category_id,
                        'brand_id' => null,
                        'amount' => $amount,
                        'uom' => $uom,
                        'packed_date' => Carbon::today(),
                        'fund_id' => $batch->fund_id,
                    ],
                ];

//            dump($packages_created);

                $transfer_resp = $batch->transfer(
                    $used_weight,
                    $qty_to_xfer,
                    $packages_created,
                    $batch->name
                );

                if ($transfer_resp instanceof QueryException) {
                    flash()->error($transfer_resp->getMessage());

//                    return back(route('batches.transfer', $batch->id))
                    return redirect(route('purchase-orders.show', $purchaseOrder->id))
//                    ->withErrors($e->getMessage())
                        ->withInput($request->all());
                }
                $tag_id++;

                $batch->inventory = (float) bcsub($batch->inventory, $batch->inventory, 4);
                $batch->transfer = (float) bcadd($batch->inventory, $batch->inventory, 4);

                $batch->save();
            }

            return redirect(route('purchase-orders.retag', $purchaseOrder->id));
        }
        $purchaseOrder->load(
            'vendor',
            'customer',
            'batches.children_batches.parent_batch.transfer_log',
            'batches.children_batches.order_details.sale_order.destination_license',
            'batches.children_batches.order_details.order_detail_returned',
            'batches.children_batches.order_details.batch',
            'batches.brand',
            'batches.category',
            'batches.cultivator',
            'batches.fund',
            'batches.order_details_accepted',
            'fund',
            'destination_license.license_type');
//        dd($purchaseOrder);

//        $b = Batch::find(12512);
//        dd($b->toJson());
        //dd($b->available_weight_grams);

        return view('purchase_orders.retag', compact('purchaseOrder'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {

        $purchaseOrder->update([
            'notes'=>$request->get('notes')
        ]);

        return redirect(route('purchase-orders.show', $purchaseOrder->id));
    }

    public function addBatch(Request $request, PurchaseOrder $purchaseOrder)
    {
        try {
            DB::beginTransaction();

            $destination_location = Location::findOrFail($purchaseOrder->location_id);

            if ($path = $request->file('_packages')) { //process file

                $packages = collect(array_map('str_getcsv', file($path->getRealPath())));

                //remove header
                $packages->pull(0);

                $errors=collect();
                //get category & brand ids & validate
                $packages->transform(function ($row, $idx) use ($errors) {

                    if(!empty($row[0])) {
                        $category = Category::select('id')->where('name', $row[0])->first();
                        if(is_null($category)) {
                            $errors->push("Row ".$idx.": Category name invalid, can't find match!");
                        } else {
                            $row['category_id'] = $category->id;
                        }
                    } else {
                        $errors->push("Row ".$idx.": Category required.");
                    }

                    if (!empty($row[2])) {
                        $brand = Brand::select('id')->where('name', $row[2])->first();
                        if(is_null($brand)) {
                            $errors->push("Row ".$idx.": Brand name invalid, can't find match!");
                        } else {
                            $row['category_id'] = $brand->id;
                        }
                    }

                    if (empty($row[3])) {
                        $errors->push("Row ".$idx.": Name required");
                    }

                    if (empty($row[6])) {
                        $errors->push("Row ".$idx.": Qty must be a positive number");
                    }

                    if (empty($row[7]) || (!empty($row[7]) && !in_array($row[7], (config('inventorymgmt.uom'))) )) {
                        $errors->push("Row ".$idx.": Invalid UOM");
                    }

                    $row['total_cost'] = ((float)$row[6] * (float)$row[8]);

                    return $row;
                });

                if($errors->count()) {
                    throw new Exception($errors->join('<br>'));
                }

                $data['_batches'] = [
                    'ref_number' => $packages->pluck(1)->toArray(),
                    'category_id' => $packages->pluck('category_id')->toArray(),
                    'brand_id' => $packages->pluck('brand_id')->toArray(),
                    'name' => $packages->pluck(3)->toArray(),
                    'allocated_name' => $packages->pluck(4)->toArray(),
                    'type' => $packages->pluck(5)->toArray(),
                    'quantity' => $packages->pluck(6)->toArray(),
                    'uom' => $packages->pluck(7)->toArray(),
                    'unit_cost' => $packages->pluck(8)->toArray(),
                    'total_cost' => $packages->pluck('total_cost')->toArray(),
                    'suggested_unit_sale_price' => $packages->pluck(9)->toArray(),
                    'min_flex' => $packages->pluck(10)->toArray(),
                ];

                $batch_added = 0;

                for ($i = 0; $i < count($data['_batches']['ref_number']); $i++) {
                    $batch = [];
                    $batch['purchase_order_id']=$purchaseOrder->id;
                    foreach ($data['_batches'] as $field => $vals) {
                        $batch[$field] = $vals[$i];
                    }

                    Batch::createBatch($batch, $purchaseOrder->location_id);

                    $batch_added++;

                }

                if ($batch_added) {
                    flash()->success("<strong>{$batch_added}</strong> items added successfully!");
                }

            } else {

                $batch = request()->get('_batches');

                $batch['purchase_order_id'] = $purchaseOrder->id;

                Batch::createBatch($batch, $purchaseOrder->location_id);

                flash()->success($batch['name'].' added successfully!');
            }

            $purchaseOrder->refresh();
            $purchaseOrder->updateTotals();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
//            dd($e->getFile());
            flash()->error($e->getMessage());

            return redirect(route('purchase-orders.show', $purchaseOrder->id))
                ->withInput($request->all());
        }

        return redirect(route('purchase-orders.show', $purchaseOrder->id));
    }

    public function updateBatch(Request $request, PurchaseOrder $purchaseOrder, Batch $batch)
    {
        $purchaseOrder->load('transactions');

//        if($batch->order_details->isNotEmpty()) {
//            flash()->error('Unable to update batch# '.$purchaseOrder->ref_number);
//            return redirect(route('purchase-orders.show', $purchaseOrder->id));
//        }

        try {
            DB::beginTransaction();

            if ((float) $request->units_purchased === 0.0) {
                $batch->delete();

            //activity loggin in event listener
            } else {
                $originalBatch = $batch->getOriginal();

                $batch->units_purchased = $request->units_purchased;
                $batch->inventory = $request->units_purchased;
                $batch->unit_price = $request->unit_price;
                $batch->subtotal_price = $request->units_purchased * $request->unit_price;

                if($batch->allocated_inventory->count() > 1) {
                    throw new \Exception("There is activity on this batch please review.");
                }

                $batch->allocated_inventory->first()->batch_location->quantity = $batch->inventory;
                $batch->allocated_inventory->first()->batch_location->unit_price = $request->unit_price;

                $batch->push();

                if ($changes = $batch->getChanges()) {
                    $activity_prop = collect([
                        'Batch ID' => $batch->id,
                        'SKU' => $batch->ref_number,
                    ]);

                    if (! empty($changes['units_purchased'])) {
                        $activity_prop->put('Units', $originalBatch['units_purchased'].' '.$batch->uom.' -> '.$batch->units_purchased.' '.$batch->uom);
                    }

                    if (! empty($changes['unit_price'])) {
                        $activity_prop->put('Unit Cost', display_currency($originalBatch['unit_price']).' -> '.display_currency($request->unit_price));
                    }

                    activity('batch')
                        ->causedBy(Auth::user())
                        ->performedOn($batch)
                        ->withProperties($activity_prop)
                        ->log('Updated');

                    activity('purchase-order')
                        ->causedBy(Auth::user())
                        ->performedOn($purchaseOrder)
                        ->withProperties($activity_prop)
                        ->log('Updated');
                }
            }

            $purchaseOrder->load('batches');

            $purchaseOrder->updateTotals();

            DB::commit();

            flash()->success('Purchase Order #'.$purchaseOrder->ref_number.' Updated');

            return redirect(route('purchase-orders.show', $purchaseOrder->id));
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error('Unable to update: '.$e->getMessage());

            return redirect(route('purchase-orders.show', $purchaseOrder->id));
        }
    }

    public function updateBatchInventory(Request $request, PurchaseOrder $purchaseOrder, Batch $batch)
    {
        try {
            DB::beginTransaction();

            $change = bcsub($request->get('batch_inventory'), $batch->available_for_allocation,4);

            if (bcadd($batch->inventory, $change) < 0) {
                throw new Exception('Unable to update quantity, try again.');
            }

            $batch->units_purchased = bcadd($batch->units_purchased, $change,4);
            $batch->inventory = bcadd($batch->inventory, $change,4);
            $batch->subtotal_price = ($batch->units_purchased * $batch->unit_price);
            $batch->save();
            //dd($batch);
            $purchaseOrder->load('batches');
            $purchaseOrder->updateTotals();

            $activity_prop = collect([
                'Batch ID' => $batch->id,
                'SKU' => $batch->ref_number,
                'Change' => ($change > 0 ? '+'.$change : $change).' '.$batch->uom,
                'Amount' => display_currency($change * $batch->unit_price),
            ]);

            activity('purchase-order')
                ->causedBy(Auth::user())
                ->performedOn($purchaseOrder)
                ->withProperties($activity_prop)
                ->log(($change > 0 ? 'Additional Purchase' : 'Return'));

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error('Unable to update: '.$e->getMessage());
        }

        return redirect(route('purchase-orders.show', $purchaseOrder->id));
    }

//    public function payment(PurchaseOrder $purchaseOrder)
//    {
//        $payment = request('payment');
//        $txn_date = request('txn_date');
//        $payment_method = request('payment_method');
//        $ref_number = request('ref_number');
//        $location_id = request('location_id');
//        $memo = request('memo');
////        $order_detail_ids = request('order_detail_ids');
//
//        $params = [$purchaseOrder];
//
//        try {
//            DB::beginTransaction();
//
////            OrderDetail::whereIn('id', explode(",", $order_detail_ids))->update(['journal_transaction_id'=>$transaction->id]);
//
//            $purchaseOrder->applyPayment($payment, $txn_date, $payment_method, $ref_number, $memo, null, $location_id);
//
//            DB::commit();
//
//            flash()->success('Payment applied');
//        } catch (\Exception $e) {
//            DB::rollBack();
//            flash()->error($e->getMessage());
//
////            if($order_detail_ids) {
////                $params = [$purchaseOrder, "odi"=>$order_detail_ids, "a"=>$payment, "lid"=>0];
////            }
//        }
//
//        return redirect(route('purchase-orders.show', $params));
//    }

    public function resetFilters(PurchaseOrderFilters $purchaseOrderFilters)
    {
        $purchaseOrderFilters->resetFilters();

        return redirect(route('purchase-orders.index'));
    }

    public function activityLog(Request $request, PurchaseOrder $model)
    {
        view()->share('title', 'Purchase Order / Activity Log');

        $model->load('activity_logs.causer');

        $heading3 = $model->ref_number;

        $back_link = route('purchase-orders.show', $model->id);

        return view('activity-log', compact('model', 'heading3', 'back_link'));
    }

    public function restore(PurchaseOrder $purchaseOrder)
    {
        try {
            DB::beginTransaction();

            activity('purchase-order')
                ->causedBy(Auth::user())
                ->performedOn($purchaseOrder)
//                ->withProperties($discount_details)
                ->log('Restored');

            $purchaseOrder->status = 'open';
            $purchaseOrder->save();

            $purchaseOrder->restore();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());

            return redirect(route('purchase-orders.show', $purchaseOrder->id));
        }

        return redirect(route('purchase-orders.show', $purchaseOrder->id));
    }

    public function allocateItems(PurchaseOrder $purchaseOrder)
    {
        if (Auth::user()->level() < 60) {
            flash()->error('Access Denied');

            return back();
        }

        view()->share('title', 'Purchase Order / Allocate Items');

        $purchaseOrder->load(
            'batches.category',
            'batches.locations',
            'batches.brand',
            'batches.approved_inventory_by_location',
            'batches.sold_inventory_by_location',
            'batches.allocated_inventory'
        );

        $purchaseOrder->batches->transform(function ($batch) {
            if (Auth::user()->hasLocation()) {
                $batch->qty_to_allocate = floatval($batch->available_for_sale);
            } else {
                $batch->qty_to_allocate = floatval($batch->available_for_allocation);
            }

            return $batch;
        });

        $locations = Location::where('active', 1)->orderBy('name')->with([
            'batches' => function ($q) use ($purchaseOrder) {
                $q->whereIn('batch_id', $purchaseOrder->batches->pluck('id'));
            }, ])->get();

        return view('purchase_orders.allocate', compact(
            'purchaseOrder',
            'locations'
        ));
    }

    public function allocateItemsStore(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (Auth::user()->level() < 60) {
            flash()->error('Access Denied');

            return back();
        }

        $batches_to_allocate = $request->get('batches');

        try {
            DB::beginTransaction();

            $allocate_errors = collect();
            $allocate_success = collect();

            foreach ($batches_to_allocate as $batch_id => $locations) {
                $batch = Batch::find($batch_id);

                $locations = collect($locations['locations']);

                $avail_to_allocate = (Auth::user()->hasLocation() ? $batch->available_for_sale : $batch->available_for_allocation);

                if (bccomp($locations->sum('quantity'), $avail_to_allocate, 4) > 0) {
                    $allocate_errors->push("<strong>{$batch->name}:</strong> Can not allocate {$locations->sum('quantity')} {$batch->uom}. Available: ".$avail_to_allocate.' '.$batch->uom);
                    continue;
                }

                //remove locations that have null quantity
                $new_locations = $locations->reject(function ($value, $key) {
                    return is_null($value['quantity']);
                });

                if (! $new_locations->sum('quantity')) {
                    continue;
                }

                $new_locations->each(function ($batch_location, $location_id) use ($batch, $allocate_errors) {

//                    if(Auth::user()->hasLocation() && $batch_location['quantity'] < 0) {
//                        throw new \Exception('Unable to pull inventory back from a location. Inventory allocation must be initiated from the source location.');
//                    }
//dd($batch_location);
                    debug($batch->id);
                    if($batch_location['quantity'] < 0 && ($batch->unit_price != $batch_location['unit_price'])) {
                        $allocate_errors->push("<strong>{$batch->name}:</strong> Unable to allocate inventory back to Main because location ".$batch_location['location_name'].' unit cost ('.display_currency($batch_location['unit_price']).') is different than original unit cost ('.display_currency($batch->unit_price).').');
                        return false;
                    }

                    $batch->allocate(
                        (Auth::user()->hasLocation() ? Auth::user()->current_location->id : null),
                        $location_id,
                        $batch_location['quantity'],
                        $batch_location['name'],
                        $batch->unit_price,
                        $batch_location['suggested_unit_sale_price'],
                        $batch_location['min_flex']
                    );
                });

                $allocate_success->push("<strong>{$batch->name}</strong> allocated total {$locations->sum('quantity')} {$batch->uom} successfully.");
            }

            if ($allocate_errors->count()) {
                throw new \Exception($allocate_errors->implode('<br>'));
            }

            DB::commit();

            flash()->success($allocate_success->implode('<br>'));

            return redirect(route('purchase-orders.show', $purchaseOrder->id));
        } catch (\Exception $e) {
            DB::rollBack();
            flash()->error($e->getMessage());

            return back()->withInput($request->all());
        }
    }

}
