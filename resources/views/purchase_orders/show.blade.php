
@extends('layouts.app')


@section('content')

    @if($purchaseOrder->trashed())
        <div class="alert alert-danger alert-dismissable">
            VOIDED ORDER!
        </div>
    @endif

    <div class="clearfix">

        <div class="pull-left">

            <a href="{{ route('vendors.show', $purchaseOrder->vendor) }}" class="btn btn-primary ml-2">Vendor Profile <i class="mdi mdi-account"></i> </a>

            @can('accounting.payables')
            <a href="{{ route('accounting.payables', $purchaseOrder->id) }}" class="btn btn-primary ml-2">Payables <i class=" mdi mdi-currency-usd"></i> </a>
            @endcan
        </div>

        <div class="pull-right">

            @level(60)
            <a href="{{ route('purchase-orders.activity-log', $purchaseOrder->id) }}" class="btn btn-secondary waves-effect waves-light">Activity Log <i class="ion-ios7-timer-outline"></i></a>
            @endlevel

            <a href="{{ route('purchase-orders.print_po', $purchaseOrder->id) }}" class="btn btn-secondary waves-effect waves-light ml-1">Print <i class="ti-receipt"></i></a>

            @if( $purchaseOrder->canBeDeleted)
                {{ Form::open(['class'=>'form-horizontal d-inline', 'url'=>route('purchase-orders.remove', $purchaseOrder->id)]) }}
                <button type="submit" class="btn btn-danger waves-effect waves-light ml-1" onclick="return confirm('Are you sure you want to delete this purchase order?')">Void</button>
                {{ Form::close() }}
            @endif

            @if( $purchaseOrder->trashed())
                {{ Form::open(['class'=>'form-horizontal d-inline', 'url'=>route('purchase-orders.restore', $purchaseOrder->id)]) }}
                <button type="submit" class="btn btn-success waves-effect waves-light ml-1" onclick="return confirm('Are you sure you want to restore this purchase order?')">Restore</button>
                {{ Form::close() }}
            @endif

        </div>
    </div>

    <br>

    <div class="row">

        <div class="col-lg-12">
            <div class="card-box">

                <div class="row">

                    <div class="col-md-5 col-sm-6">

                        <h4 class="m-t-0 m-b-20 header-title">Summary</h4>

                        @include('purchase_orders._summary', ['purcahseOrder'=>$purchaseOrder])

                    </div>

                    <div class="col-md-4 col-sm-6">

                        <h4 class="m-t-0 m-b-20 header-title">Notes</h4>

                        {{ Form::open(['url'=>route('purchase-orders.update', $purchaseOrder), 'method'=>'put']) }}
                        <textarea class="form-control" id="notes" name="notes" rows="5">{{ nl2br($purchaseOrder->notes) }}</textarea>
                        <button type="submit" class="btn btn-primary btn-sm waves-effect waves-light m-t-10">Save</button>
                        {{ Form::close() }}


                    </div>

                    <div class="col-md-3 col-sm-12">

                        <div class="pull-right">

                            <div class="pull-right">

                                <h4 class="text-right m-t-0 m-b-20 header-title">Balance Due</h4>

                                <h1 class="text-right">{{ display_currency($purchaseOrder->balance) }}</h1>

                                @can('users.payment.vendor')
                                @if($purchaseOrder->balance)
                                <p class="text-right"><a href="{{ route('vendors.payments', ['vendor'=>$purchaseOrder->vendor_id, 'purchase_order'=>$purchaseOrder]) }}" class="btn btn-lg btn-primary">Pay</a></p>
                                @endif
                                @endcan

                                @if($purchaseOrder->transactions->count())
                                <a href="javascript:void(0)" class="" data-toggle="modal" data-target=".payments-info">{{ $purchaseOrder->transactions->count() }} {{ Str::of('payment')->plural($purchaseOrder->transactions->count()) }} made ({{ display_currency($purchaseOrder->transactions->sum('amount')) }})</a>
                                @endif

                                <div class="modal fade payments-info" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;" aria-hidden="true">
                                    <div class="modal-dialog modal-lg" style="max-width: 75% !important;">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                                <h4 class="modal-title" id="mySmallModalLabel">Payments for {{ $purchaseOrder->ref_number }}</h4>
                                            </div>
                                            <div class="modal-body">

                                                <div class="table-responsive" style="height: 300px; overflow: scroll;">
                                                    <table class="table table-hover table-striped">
                                                        <thead>
                                                        <tr>
                                                            <th>Txn #</th>
                                                            <th>Date</th>
                                                            <th>Amount</th>
                                                            <th>Location</th>
                                                            <th>Method</th>
                                                            <th>Ref#</th>
                                                            <th>Memo</th>
                                                            <th>By</th>
                                                        </tr>
                                                        </thead>
                                                        <tbody>

                                                        @foreach($purchaseOrder->transactions->sortByDesc('txn_date') as $transaction)
                                                            <tr>
                                                                <td>{{ $transaction->id }}</td>
                                                                <td>{{ $transaction->txn_date() }}</td>
                                                                <td>{{ display_currency($transaction->amount) }}</td>
                                                                <td>{{ ($transaction->location?$transaction->location->name:"The Nest") }}</td>
                                                                <td>{{ $transaction->payment_method }}</td>
                                                                <td>{{ $transaction->ref_number }}</td>
                                                                <td>{{ $transaction->memo }}</td>
                                                                <td>{{ $transaction->user->name }}</td>
                                                            </tr>

                                                        @endforeach

                                                        </tbody>
                                                        <tfoot>
                                                        <tr>
                                                            <td></td>
                                                            <td></td>
                                                            <th>{{ display_currency($purchaseOrder->transactions->sum('amount')) }}</th>
                                                            <td colspan="5"></td>
                                                        </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>

                                            </div>
                                        </div><!-- /.modal-content -->
                                    </div><!-- /.modal-dialog -->
                                </div>



                            </div>

                        </div>

                    </div>

                </div>



            </div>
        </div>
    </div>

    @if(! $purchaseOrder->trashed())

    {{ Form::open(['id'=>'add-batch-item', 'class'=>'form-horizontal', 'files'=>'true', 'url'=>route('purchase-orders.add-batch', $purchaseOrder->id), 'method'=>'post']) }}

        <div class="card-box">
            <ul class="nav nav-tabs tabs-bordered">
                <li class="nav-item">
                    <a href="#add_single" data-toggle="tab" aria-expanded="false" class="nav-link active">ADD ITEM</a>
                </li>

                <li class="nav-item">
                    <a href="#upload" data-toggle="tab" aria-expanded="false" class="nav-link">UPLOAD ITEMS</a>
                </li>
            </ul>

            <div class="tab-content">

            <div class="tab-pane active" id="add_single" style="">

                <div class="batch_items">

                    <div class="add_batch_row mb-3">

                        @include('_partials/_add_batch_item', ['show_non_inventory_item' => false, 'location_id'=>$purchaseOrder->location_id])

                    </div>

                    <button type="submit" class="btn btn-primary waves-effect waves-light add_batch_submit">Add Item</button>

                </div>

            </div>

            <div class="tab-pane" id="upload" style="">

                <div class="upload-batches">

                    <h4>CSV Data File Schema</h4>

                    <h5>Columns:</h5>
                    <ol>
                        <li>Category <small class="text-danger">(Required)</small>
                            <ul>
                                <li>Must match existing category name. If no match, upload will fail.</li>
                            </ul>
                        </li>
                        <li>SKU <small>(Optional)</small></li>
                        <li>Brand <small>(Optional)</small>
                            <ul>
                                <li>Must match existing brand name. If no match, brand will be ignored.</li>
                            </ul>
                        </li>
                        <li>Name <small class="text-danger">(Required)</small></li>
                        <li>Sell As Name </li>
                        <li>Product Type <small>(Optional - {{ implode(',', config('inventorymgmt.product_type')) }})</small></li>
                        <li>Quantity <small class="text-danger">(Required)</small></li>
                        <li>Unit Of Measure (<small class="text-danger">(Required)</small>
                            <ul>
                                <li><small>({{ implode(',', config('inventorymgmt.uom')) }})</small></li>
                            </ul>
                        </li>
                        <li>Unit Cost <small class="text-danger">(Required)</small></li>
                        <li>Sale Price <small class="text-danger">(Required)</small></li>
                        <li>Min. Flex <small>(Optional)</small></li>
                    </ol>

                    <a href="/purchase-order/Batch-Data.csv" target="_blank">Download Example</a>

                    <hr>
                    {{ Form::file('_packages', ['class'=>'required']) }}

                    <br>
                    <br>
                    <button type="submit" class="btn btn-primary waves-effect waves-light add_batch_submit">Upload Items</button>
                </div>

            </div>

            </div>
        </div>
    {{ Form::close() }}

    @endif

@if($purchaseOrder->batches->count())

    <hr>
    <h4 class="m-t-0 header-title">Items <span class="badge badge-info">{{ $purchaseOrder->batches->count() }}</span></h4>
    <div class="row">

        <div class="col-lg-12">

            <div class="card">
                <div class="card-block">

{{--                    <a href="{{ route('purchase-orders.allocate-items', $purchaseOrder) }}" class="btn btn-primary waves-effect waves-light pull-left ml-2">Allocate Items <i class="mdi mdi-logout"></i></a>--}}

                    @if( $purchaseOrder->canBeDeleted)
                        <a href="{{ route('purchase-orders.remove-all-items', $purchaseOrder) }}" class="btn btn-danger waves-effect waves-light pull-right mb-2">Delete All Items <i class="ion-close"></i></a>
                    @endif
                    <a href="{{ route('purchase-orders.return-items', $purchaseOrder) }}" class="btn btn-danger waves-effect waves-light pull-right mr-2">Return Items <i class="mdi mdi-open-in-new"></i></a>
                    <div class="clearfix"></div>

                    <div class="table-responsive">
                <table class="table m-t-30 table-hover table-striped">
                    <thead>
                    <tr>
                        <th>Name<br>SKU</th>
                        <th>Qty @ Unit Cost</th>
                        <th>Inventory</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($purchaseOrder->batches as $batch)
{{--                        {{ dump($batch) }}--}}
                    <tr>
                        <td style="white-space: nowrap">
                            @if($batch->brand) <strong>{{ $batch->brand->name }}</strong><br> @endif

                            <strong>{{ $batch->category->name }}: {{ $batch->present()->non_branded_name }}</strong><br>
                                <a href="{{ route('batches.show', $batch->id) }}">{{ $batch->ref_number }}</a><br>
                                <i>
                                @if(Auth::user()->hasLocation())
                                    <span>Original Name: {{ $batch->getRawOriginal('name') }}</span>
                                @else
                                    <span>Allocated Name: {{ $batch->allocated_inventory->first()->batch_location->name }}</span>
                                @endif
                                </i>
                        </td>
                        <td style="white-space: nowrap">

                            @if( $batch->canChangePOQuantityPrice() )

                                {{ Form::open(['url'=>route('purchase-orders.update-batch', [$purchaseOrder->id, $batch->id])]) }}
                                {{ method_field('PUT') }}

                                <div class="row">

                                    <div class="col-12 col-xl-5">
                                        @if($batch->track_inventory)
                                        <div class="input-group mb-2">
                                            <input type="text" class="form-control" name="units_purchased" value="{{ $batch->units_purchased }}">
                                            <span class="input-group-addon">{{ $batch->uom }}</span>
                                        </div>
                                        @else
                                        <i>Non-inventory Item</i>
                                        @endif
                                    </div>

                                    <div class="col-12 col-xl-5">
                                        <div class="input-group mb-2">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" step="0.01" class="form-control" name="unit_price" value="{{ display_currency_no_sign($batch->original_unit_price) }}">
                                        </div>
                                    </div>

                                    <div class="col-12 col-xl-2"><button type="submit" class="btn btn-primary waves-effect waves-light">Save</button></div>

                                </div>

                                {{ Form::close() }}

                            @else

                                <p>
                                    @if($batch->track_inventory)
                                    {{ $batch->units_purchased }} {{ $batch->uom }}
                                    @else
                                    <i>Unlimited</i>
                                    @endif
                                    @ {{ display_currency($batch->avg_unit_price) }}</p>


                                @if($batch->avg_unit_price && $batch->original_unit_price != $batch->avg_unit_price)
                                    <small><i>Original Unit Cost: {{ display_currency($batch->original_unit_price) }}</i></small>
                                @endif

                            @endif

                        </td>

                        <td style="white-space: nowrap; min-width: 125px;width: 125px;">
                            @if($batch->track_inventory)

                                @if($batch->allocated_and_sold_inventory->count() > 1)
                                <dl class="row">

                                    @foreach($batch->allocated_and_sold_inventory->sortBy('name')->groupBy('name') as $location_name => $locations)
                                        <dt class="col-4">{{ $location_name }}:</dt>
                                        <dd class="col-8">{{ $locations->sum('batch_location.quantity') }} {{ $batch->uom }}</dd>
                                    @endforeach

                                <dt class="col-4">Total:</dt>
                                <dd class="col-8">{{ $batch->allocated_and_sold_inventory->sum('batch_location.quantity') }} {{ $batch->uom }}</dd>
                                </dl>
                                    @else
                                    {{ $batch->allocated_and_sold_inventory->sum('batch_location.quantity') }} {{ $batch->uom }}
                                    @endif
                            @else
                                <i>Unlimited</i>
                            @endif
                        </td>

                        <td class="text-right"><strong>{{ display_currency($batch->subtotal_price) }}</strong></td>

                    </tr>
                    @endforeach

                    </tbody>

                    <tfoot>
                    <tr>
                        <td colspan="3"></td>
                        <td class="text-right"><h3>{{ display_currency($purchaseOrder->batches->sum('subtotal_price')) }}</h3></td>

                    </tr>

                    </tfoot>

                </table>
            </div>
                </div>
            </div>
        </div>
    </div>

@endif

@if($purchaseOrder->return_purchase_orders->count())
<hr>
    <h4 class="m-t-0 header-title">Return Orders <span class="badge badge-info">{{ $purchaseOrder->return_purchase_orders->count() }}</span></h4>

<div class="row">

    <div class="col-lg-12">

        <div class="card-box">

{{--            <div class="row">--}}
{{--                <div class="col-lg-12">--}}
{{--                    <p><a href="{{ route('sale-orders.export') }}" class="pull-right btn btn-primary ">Export Sale Orders</a><br></p>--}}
{{--                </div>--}}
{{--            </div>--}}

{{--            <p>Showing {{ $sale_orders->firstItem() }} to {{ $sale_orders->lastItem() }} of {{ $sale_orders->total() }}</p>--}}

            <table id="sale-order-table" class="table table-hover">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>RPO#</th>
                    <th>Total</th>
                </tr>
                </thead>

                <tbody>
                @foreach($purchaseOrder->return_purchase_orders as $return_purchase_order)
                    <tr>
                        <td>{{ $return_purchase_order->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                        <td class="text-nowrap">

                            <a href="javascript:void(0)" data-toggle="modal" data-target=".rpo-{{ $return_purchase_order->id }}">{{ $return_purchase_order->ref_number }}</a>

                            <div class="modal fade rpo-{{ $return_purchase_order->id }}" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                            <h4 class="modal-title" id="mySmallModalLabel">Returned Items - {{ $return_purchase_order->ref_number }}</h4>
                                        </div>
                                        <div class="modal-body">
                                            <table class="table">
                                                <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>SKU</th>
                                                    <th>Returned From</th>
                                                    <th>Qty @ Cost</th>
                                                    <th>Subtotal</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($return_purchase_order->order_details as $retur_order_detail)
                                                    <tr>
                                                        <td>{{ $retur_order_detail->batch->name }}</td>
                                                        <td>{{ ($retur_order_detail->batch->ref_number) }}</td>
                                                        <td>{{ ($retur_order_detail->location?$retur_order_detail->location->name:"Nest") }}</td>
                                                        <td>{{ $retur_order_detail->units_accepted }} {{ $retur_order_detail->batch->uom }} @ {{ display_currency($retur_order_detail->unit_cost) }}</td>
                                                        <td>{{ display_currency($retur_order_detail->line_cost) }}</td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                                <tfoot>
                                                <th colspan="3"></th>
                                                <th class="text-right">Total:</th>
                                                <th>{{ display_currency($return_purchase_order->order_details->sum('line_cost')) }}</th>
{{--                                                <th>{{ display_currency(collect($location_balances['remaining_items'])->sum('subtotal_cost')) }}</th>--}}
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div><!-- /.modal-content -->
                                </div><!-- /.modal-dialog -->
                            </div>

                        </td>
                        <td>{{ display_currency($return_purchase_order->total) }}</td>
                    </tr>
                @endforeach

                </tbody>

                <tfoot>

                <tr>
                    <th colspan="2" class="text-right">Total:</th>
                    <th>{{ display_currency($purchaseOrder->return_purchase_orders->sum('total')) }}</th>
                </tr>

                </tfoot>

            </table>

        </div>

    </div>

</div>

@endif

@endsection


@section('js')



@endsection