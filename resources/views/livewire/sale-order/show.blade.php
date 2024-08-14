<div>

    @if (session()->has('success-message'))
        <div class="alert alert-success">
            {{ session('success-message') }}
        </div>
    @endif

    @if (session()->has('error-message'))
        <div class="alert alert-danger">
            {{ session('error-message') }}
        </div>
    @endif

    @if($saleOrder->requires_manager_approval)
        <div class="alert alert-warning alert-dismissable">
            This order requires manager approval due to discounts.

            @level(60)
            <hr>
            {{ Form::open(['class'=>'form-horizontal', 'wire:submit.prevent="approveDiscount"']) }}
            <button class="btn btn-success waves-effect waves-light pull-left mr-2" type="submit">Approve</button>
            {{ Form::close() }}

            {{ Form::open(['class'=>'form-horizontal', 'wire:submit.prevent="rejectDiscount"']) }}
            <button class="btn btn-danger waves-effect waves-light pull-left" type="submit" onclick="return confirm('This will remove all discounts from this order.')">Reject</button>
            {{ Form::close() }}
            <div class="clearfix"></div>

            @endlevel
        </div>
    @endif

    @if($saleOrder->trashed())
        <div class="alert alert-danger alert-dismissable">
            VOIDED ORDER!
        </div>
    @endif

    <div class="clearfix">

        <div class="pull-left">
            @level(60)
            <a href="{{ route('sale-orders.activity-log', $saleOrder->id) }}" class="btn btn-primary waves-effect waves-light mb-2">Activity Log <i class="ti-receipt"></i></a>
            @endlevel
        </div>

        <div class="pull-right">

            @if($saleOrder->trashed())

                {{ Form::open(['class'=>'form-horizontal', 'url'=>route('sale-orders.restore', $saleOrder->id)]) }}
                <button type="submit" class="btn btn-success waves-effect waves-light" onclick="return confirm('Are you sure you want to restore this order?')">RESTORE</button>
                {{ Form::close() }}

            @elseif($saleOrder->canVoid())

                {{ Form::open(['class'=>'form-horizontal', 'url'=>route('sale-orders.remove', $saleOrder->id)]) }}
                <button type="submit" id="void_order" data-conf-message="{{ $deliver_conf_message }}" class="btn btn-danger waves-effect waves-light pull-right ">VOID</button>
                {{ Form::close() }}

            @else
                @if($saleOrder->isDelivered())
                    <a href="{{ route('sale-orders.invoice', $saleOrder->id) }}" class="btn btn-dark waves-effect waves-light">Invoice <i class="ti-receipt"></i></a>
                @endif
            @endif
        </div>
    </div>

    <div class="row" wire:loading.class="opacity-70">

        <div class="col-lg-12 mb-3">

            <div class="card">
                <div class="card-block">

                    <div class="row">
                        <div class="col-md-5 col-sm-6">

                            <h4 class="m-t-0 m-b-20 header-title">Summary</h4>

                            <dl class="row">
                                <dt class="col-5  text-right">Order#:</dt>
                                <dd class="col-7 ">{{ $saleOrder->ref_number }}</dd>

                                <dt class="col-5 text-right">Order Date:</dt>
                                <dd class="col-7">{{ $saleOrder->txn_date->format(config('inventorymgmt.date_format')) }}</dd>

                                @if($saleOrder->parent_order)
                                    <dt class="col-5  text-right">Original Order#:</dt>
                                    <dd class="col-7 "><a href="{{ route('sale-orders.show', $saleOrder->parent_order) }}">{{ $saleOrder->parent_order->ref_number }}</a></dd>
                                @endif

                                <dt class="col-5  text-right">Entered By:</dt>
                                <dd class="col-7 ">{{ $saleOrder->user->name }}</dd>

                                <dt class="col-5  text-right">Status:</dt>
                                <dd class="col-7 "><span class="badge badge-{{ status_class($saleOrder->status) }}"> {{ ucwords($saleOrder->status) }} </span>
                                    @if($saleOrder->status=='delivered' && !empty($saleOrder->delivered_at))
                                        <br>{{ $saleOrder->delivered_at->format(config('inventorymgmt.date_time_format')) }}
                                    @endif
                                </dd>

                                <dt class="col-5 text-right">Location:</dt>
                                <dd class="col-7">
                                    {{ $saleOrder->location->name }}{!! ($saleOrder->location->trashed()?" <span class='text-danger'>(deleted)</span>":"") !!}
                                </dd>

                                @if($saleOrder->sales_rep)
                                    <dt class="col-5 text-right">Sales Rep:</dt>
                                    <dd class="col-7"><a href="{{ route('users.show', $saleOrder->sales_rep->id) }}">{{ $saleOrder->sales_rep->name }}</a></dd>
                                @endif

                                <dt class="col-5 text-right">Customer:</dt>
                                <dd class="col-7"><a href="{{ route('users.show', $saleOrder->customer->id) }}">{{ $saleOrder->customer->name }}</a></dd>

                                <dt class="col-5 text-right">Customer Credit:</dt>
                                <dd class="col-7"><span class="badge badge-success">{{ display_currency($saleOrder->customer->available_balance) }}</span></dd>
                            </dl>
                            <hr>

                            <dl class="row">

                                <dt class="col-5 text-right">Subtotal:</dt>
                                <dd class="col-7 ">{{ display_currency($saleOrder->subtotal) }}</dd>

                                    <dt class="col-5 text-right">Discount:</dt>
                                    <dd class="col-6">
                                        @if($saleOrder->canAddItems())
                                        @level(50)
                                        <div>
                                        {{ Form::open(['url'=>'#', 'wire:submit.prevent="applyDiscount"']) }}

                                        <div class="input-group mb-1">
                                            <input wire:model.defer="saleOrder.discount_applied" class="form-control" name="discount_applied" autocomplete="off">
                                            <select wire:model.defer="saleOrder.discount_type" class="form-control" name="discount_type" style="height: calc(1.75rem + 3px)">
                                                <option value="amt">$</option>
                                                <option value="perc">%</option>
                                            </select>
                                            <div class="input-group-btn">
                                                <button wire:loading.class="disabled" type="submit" class="btn btn-primary waves-effect waves-light"><i class=" mdi mdi-check"></i></button>
                                            </div>
                                        </div>

                                        {{ Form::close() }}
                                        </div>
                                        @endlevel
                                        @endif

                                            <div class="text-danger">({{ display_currency($saleOrder->discount) }})</div>
                                    </dd>

                                <dt class="col-5 text-right"><h4>Total:</h4></dt>
                                <dd class="col-7 "><h4>{{ display_currency($saleOrder->total) }}</h4></dd>
                            </dl>

                        </div>

                        <div class="col-md-4 col-sm-6">

                            <h4 class="m-t-0 m-b-20 header-title">Notes</h4>

                            {{ Form::open(['url'=>'#', 'wire:submit.prevent="updateSaleOrder"']) }}
                            <textarea wire:model.defer="saleOrder.notes" class="form-control" id="notes" name="notes" rows="3"></textarea>
                            <button type="submit" class="btn btn-primary btn-sm waves-effect waves-light m-t-10">Save</button>
                            {{ Form::close() }}

                            <br>
                            <br>

                            <div class="table-responsive">
                                <table class="table">

                                    <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Count</th>
                                        <th>Sales</th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    @foreach($saleOrder->order_details->groupBy('batch.category.name') as $category_name => $order_details)
                                        <tr>
                                            <td>{{ $category_name }}</td>
                                            <td>{{ $order_details->sum('units') }}</td>
                                            <td>{{ display_currency($order_details->sum('sale_price')) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                    <tfoot>
                                    <th></th>
                                    <th>{{ $saleOrder->order_details->sum('units') }}</th>
                                    <th>{{ display_currency($saleOrder->order_details->sum('subtotal')) }}</th>
                                    </tfoot>
                                </table>
                            </div>

                        </div>

                        <div class="col-md-3 col-sm-12">

                            <div class="pull-right">

                                <h4 class="text-right m-t-0 m-b-20 header-title">Balance Due</h4>

                                <h1 class="text-right">{{ display_currency($saleOrder->balance) }}</h1>

                                @if(Auth::user()->isSuperAdmin())
                                    <a class="btn btn-default"
                                            href="{{ route('sale-orders.refresh-balance', $saleOrder) }}">Refresh Balance</a>
                                @endif

                                @if(!$saleOrder->isDelivered() && !$saleOrder->trashed() && !$saleOrder->location->trashed())

                                    @level(50)
                                    <p class="text-right"><a href="javascript:void(0)" class="btn btn-lg btn-primary" data-toggle="modal" data-target=".make-payment">Receive Payment</a></p>
                                    @endlevel

                                    <div class="modal fade make-payment" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                                    <h4 class="modal-title" id="mySmallModalLabel">Balance Due: {{ display_currency($saleOrder->balance) }}</h4>
                                                    <div class="pull-right"><strong>Available Customer Credit: <span class="badge badge-success" style="font-size: 18px" >{{ display_currency($saleOrder->customer->available_balance) }}</span></strong></div>
                                                </div>
                                                <div class="modal-body">

                                                    {{ Form::open(['url'=>route('sale-orders.payment', ['sale_order'=>$saleOrder->id]), 'class'=>'prevent_double_click']) }}

                                                    @include('_payment_form', ['saleOrder'=>$saleOrder])

                                                    {{ Form::close() }}

                                                </div>
                                            </div><!-- /.modal-content -->
                                        </div><!-- /.modal-dialog -->
                                    </div>

                                @endif

                                @if($saleOrder->transactions->count())
                                    <a href="javascript:void(0)" class="" data-toggle="modal" data-target=".payments-info">{{ $saleOrder->transactions->count() }} {{ Str::plural('payment', $saleOrder->transactions->count()) }} made ({{ display_currency($saleOrder->transactions->sum('amount')) }})</a>

                                    <div class="modal fade payments-info" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" style="max-width: 75% !important;">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                                    <h4 class="modal-title" id="mySmallModalLabel">Payments for {{ $saleOrder->ref_number }}</h4>
                                                </div>
                                                <div class="modal-body">

                                                    <div class="table-responsive">
                                                        <table class="table table-hover table-striped">

                                                            <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Date</th>
                                                                <th>Applied Amount</th>
                                                                <th>Fee</th>
                                                                <th>Pmt Rcv'd</th>
                                                                <th>Crypto Rcv'd</th>
                                                                <th>Type</th>
                                                                <th>Memo</th>
                                                                <th>By</th>
                                                                <th></th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>

                                                            @foreach($saleOrder->transactions as $transaction)
                                                                <tr>
                                                                    <td>{{ $loop->iteration }}</td>
                                                                    <td>{{ $transaction->txn_date() }}</td>
                                                                    <td>{{ display_currency($transaction->amount) }}</td>
                                                                    <td>{{ ($transaction->txn_fee?display_currency($transaction->txn_fee):"--") }}</td>
                                                                    <td>{{ display_currency($transaction->amount + ($transaction->txn_fee?:0) ) }}</td>
                                                                    <td style="white-space: nowrap">
                                                                        @if($transaction->payment_method!='Cash')
                                                                            {{ $transaction->ref_number }} {{ $transaction->payment_method }}
                                                                        @else
                                                                            --
                                                                        @endif
                                                                    </td>

                                                                    <td>{{ ucfirst($transaction->type) }}</td>
                                                                    <td>{!! nl2br($transaction->memo) !!}</td>
                                                                    <td>{{ $transaction->user->name }}</td>
                                                                    <td>
                                                                        @level(50)
                                                                        @if( ! $transaction->child)
                                                                            {{ Form::open(['url'=>route('sale-orders.payment', ['sale_order'=>$saleOrder->id]), 'class'=>'prevent_double_click']) }}

                                                                            {{ Form::hidden('parent_id',$transaction->id) }}
                                                                            {{ Form::hidden('txn_date',$transaction->txn_date) }}
                                                                            {{ Form::hidden('payment_type',($transaction->type=='payment'?'refund':'payment')) }}
                                                                            {{ Form::hidden('payment_method',$transaction->payment_method) }}
                                                                            {{ Form::hidden('payment', ($transaction->amount * ($transaction->type=='refund'?-1:1))) }}
                                                                            {{ Form::hidden('txn_fee', ($transaction->txn_fee * ($transaction->type=='refund'?-1:1))) }}
                                                                            {{ Form::hidden('ref_number', ($transaction->ref_number?($transaction->ref_number * ($transaction->type=='refund'?-1:1)):null)) }}
                                                                            {{ Form::hidden('memo', "Reverse ". ucwords($transaction->type)) }}
                                                                            <button type="submit" class="btn btn-sm btn-secondary waves-effect waves-light font-14 reverse_transaction"><i class=" mdi mdi-undo-variant"></i></button>
                                                                            {{ Form::close() }}
                                                                        @endif
                                                                        @endlevel
                                                                    </td>
                                                                </tr>
                                                            @endforeach

                                                            </tbody>
                                                            <tfoot>
                                                            <tr>
                                                                <th></th>
                                                                <th></th>
                                                                <th>{{ display_currency($saleOrder->transactions->sum('amount')) }}</th>
                                                                <th>{{ display_currency($saleOrder->transactions->sum('txn_fee')) }}</th>
                                                                <th>{{ display_currency($saleOrder->transactions->sum('amount') + $saleOrder->transactions->sum('txn_fee')) }}</th>
                                                                <th colspan="5"></th>
                                                            </tr>
                                                            </tfoot>
                                                        </table>
                                                    </div>

                                                </div>
                                            </div><!-- /.modal-content -->
                                        </div><!-- /.modal-dialog -->
                                    </div>

                                @endif

                            </div>

                        </div>

                    </div>

                </div>

                @if(!$saleOrder->trashed())

                    <div class="card-footer hidden-print">

                        @can('so.ready_to_pack')
                        @if($saleOrder->isReadyToBePack() && $saleOrder->order_details->count())
                            <button wire:click="readyToPack" type="submit" class="btn btn-primary waves-effect waves-light pull-right ">Ready To Pack <i class=" mdi mdi-arrow-right-bold"></i> </button>
                        @endif
                        @endcan

                        @if($saleOrder->isReadyToDeliver())
                            <button wire:click="readyToDeliver" type="submit" class="btn btn-primary waves-effect waves-light pull-right ml-2">Ready For Delivery <i class=" mdi mdi-arrow-right-bold"></i> </button>
                        @endif

                        @if($saleOrder->isReadyForDelivery())
                            {{ Form::open(['url'=>route('sale-orders.deliver-order', ['sale_order'=>$saleOrder->id])]) }}
                            {{ method_field('PUT') }}
                            <button type="submit" class="btn btn-success waves-effect waves-light pull-right ml-2"{{ (!$saleOrder->canDeliverOrder()?"disabled":"") }}>Deliver Order <i class=" mdi mdi-arrow-right-bold"></i></button>
                            {{ Form::close() }}
                        @endif

                        @if($saleOrder->canReverse())
                            <button wire:click="reverse" type="submit" class="btn btn-secondary waves-effect waves-light pull-right"><i class=" mdi mdi-arrow-left-bold"></i> Reverse</button>
                        @endif

                    </div>

                @endif
            </div>

        </div>

    </div>

    <div class="row" wire:loading.class="opacity-70">

        <div class="col-lg-12">

            <div id="add-items" class="modal fade add-items" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;" aria-hidden="true">

                <div class="modal-dialog modal-lg" style="max-width:90% !important;">
                    <div class="modal-content">

                            @livewire('sale-order.add-item', ['sale_order'=>$saleOrder], key($saleOrder->id))

                    </div><!-- /.modal-content -->
                </div><!-- /.modal-dialog -->
            </div>

            <div class="card-box">

                <div class="row mb-3">

                    <div class="col-4">
                        <h4 class="m-t-0 header-title pull-left">Items <span class="badge badge-info">{{ $saleOrder->order_details->count() }}</span></h4>
                    </div>
                    <div class="col-4">
                        <div class="d-flex justify-content-center">
                            <div wire:loading>
                                <x-loading class="la-sm"/>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        @if(!$addItemButtonDisabled)
                        @level(50)
                        <div>
                        <button {{ $addItemButtonDisabled }} wire:click="$dispatchTo('sale-order.add-item', 'loadBatches')" type="submit" class="btn btn-lg btn-primary pull-right" data-toggle="modal" data-target=".add-items">Add Items</button>
                        </div>
                        @endlevel
                        @endif
                    </div>

                </div>

                @if($saleOrder->order_details->count())

                <div class="table-responsive">

                    <table id="order_details" class="table">

                        <thead>
                        <tr>
                            <th></th>
                            <th>Subtotal</th>

                            @if( ! $saleOrder->isHold())
                                <th>Fulfilled</th>
                            @endif

                            @if($saleOrder->canAddItems())
                                <th style="text-align: right">
                                    {{ Form::open(['url'=>'#', 'wire:submit.prevent="removeAllItems"', ]) }}
                                    {{ method_field('POST') }}
                                    <button wire:loading.attr="disabled" type="submit" class="btn btn-danger waves-effect waves-light" ><i class=" mdi mdi-delete-forever"></i> All</button>
                                    {{ Form::close() }}
                                </th>
                            @endif

                        </tr>
                        </thead>

                        <tbody>
{{--{{ dd($saleOrder->order_details) }}--}}
                        @foreach($saleOrder->order_details as $order_detail)

                            @livewire('sale-order.line-item', ['sale_order'=>$saleOrder, 'order_detail'=>$order_detail], key(Str::random()))
                        @endforeach

                        </tbody>

                    </table>
                </div>

                @endif
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('close-discount-modal', event => {
            $('#discount_modal').modal('hide');
        })
    </script>

</div>
