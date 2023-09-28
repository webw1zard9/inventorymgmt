
@extends('layouts.app')

@section('content')

    <div class="row mb-2">
        <div class="col-6">


{{--            <div class="btn-group">--}}
{{--                <button type="button" class="btn btn-primary dropdown-toggle waves-effect waves-light" data-toggle="dropdown" aria-expanded="false"><i class="ion-gear-b font-16"></i> <span class="caret"></span></button>--}}
{{--                <div class="dropdown-menu">--}}
{{--                    @if($vendor->can_edit_super_user)--}}
{{--                        @can('users.edit')--}}
{{--                            <a href="{{ route('users.edit', $vendor->id) }}" class="dropdown-item">Edit Vendor</a>--}}
{{--                        @endcan--}}
{{--                    @endif--}}
{{--                    <a href="{{ route('users.edit', $vendor->id) }}" class="dropdown-item">Create Statement</a>--}}
{{--                </div>--}}
{{--            </div>--}}

        </div>
        <div class="col-6">
            <a href="{{ route('vendors.statement', $vendor) }}" class="pull-right btn btn-primary">Create Statement <i class="ion-document-text"></i></a>
            <a href="{{ route('vendors.activity-log', $vendor) }}" class="pull-right btn btn-secondary mr-2">Activity Log <i class="ion-ios7-timer-outline"></i></a>

        </div>
    </div>


    <div class="row">

        <div class="col-sm-6">

            <div class="card-box">
                <div class="member-card">
                    <div class="text-center">
                        <h1 class="m-b-7">{{ $vendor->name }} {!! (!$vendor->active?"<span class='text-danger'>(In-Active)</span>":"") !!}
                            @if($vendor->can_edit_super_user)
                                @can('users.edit')
                                    <a href="{{ route('users.edit', $vendor->id) }}" class="ml-2"><i class="ion-edit font-16"></i></a>
                                @endcan
                            @endif
                        </h1>
                    </div>

                    <dl class="row">

                        <dt class="col-6 text-right mt-1">Available Credit:</dt>
                        <dd class="col-6">
                            <span class="badge badge-success" style="font-size: 16px" >{{ display_currency($vendor->vendor_credit_balance) }}</span>
                        </dd>

                        <dt class="col-6 text-right">Purchases:</dt>
                        <dd class="col-6">{{ display_currency($vendor->purchase_orders->sum('total')) }}</dd>

                        <dt class="col-6 text-right">Payments:</dt>
                        <dd class="col-6"><span class="text-danger">({{ display_currency($vendor_transactions->sum('amount')) }})</span></dd>

                        <dt class="col-6 text-right"><h4>Balance:</h4></dt>
                        <dd class="col-6"><h4>{{ display_currency($vendor->balance) }}</h4></dd>

                        <dt class="col-6 text-right"><h4>Due Now:</h4></dt>
                        <dd class="col-6">
                            <h4>{{ display_currency($current_payables) }}
                                @can('accounting.payables')
                                    <a class="btn btn-sm btn-secondary ml-1" href="{{ route('accounting.vendor_payables', $vendor->id) }}"> <i class=" mdi mdi-open-in-new"></i></a>
                                @endcan
                            </h4>
                        </dd>

                        <dt class="col-6"></dt>
                        <dd class="col-6">
                            @can('users.payment.vendor')
                                <div class="mt-2"><a class="btn btn-primary" href="{{ route('vendors.payments', $vendor->id) }}">Make Payment <i class="ion-social-usd"></i></a></div>
                            @endcan
                        </dd>

                    </dl>

                </div>

            </div> <!-- end card-box -->


        </div> <!-- end col -->


        <div class="col-sm-6">

            <div class="card-box">
                <div class="member-card">
                    <dl class="row">

                        <dt class="col-6 text-right"><h4>Total Inventory:</h4></dt>
                        <dd class="col-6"><h4>{{ display_currency($inventory_costs->sum('onhand_cost')) }} <i class="ion-help-circled" data-toggle="tooltip" data-placement="top" title="" data-original-title="Total inventory value on-hand. Available + hold inventory."></i></h4></dd>

                        <dt class="col-6 text-right">Available Inventory:</dt>
                        <dd class="col-6">{{ display_currency($inventory_costs->sum('available_cost')) }}</dd>

                        <dt class="col-6 text-right">Hold Inventory:</dt>
                        <dd class="col-6">{{ display_currency($inventory_costs->sum('pending_cost')) }}</dd>

                        <dt class="col-6 text-right">Fulfilled Inventory:</dt>
                        <dd class="col-6">{{ display_currency($inventory_costs->sum('fulfilled_cost')) }}</dd>
                    </dl>

                    @if(Auth::check() && Auth::user()->active_locations->count() > 1)
                    <hr>
                    <div class="text-center ">
                        <h4 class="m-b-7">Available Inventory Location Distribution</h4>
                    </div>

                    <div class="row">

                        @foreach($all_location_inventory->groupBy('location') as $location=>$per_location_inventory)
                            @if(!$per_location_inventory->sum('inventory_value')) @continue @endif

                            <div class="col-4">
                                <strong>{{ $location }}:</strong> <span class=" text-right"> {{ display_currency($per_location_inventory->sum('inventory_value')) }}</span>
                                <hr>
                                @foreach($per_location_inventory->sortByDesc('approved')->groupBy('approved') as $approved=>$location_inventory)
                                    <p class="text-{{ ($approved?"dark":"danger") }}">{{ display_currency($location_inventory->sum('inventory_value')) }} <small class="hint text-{{ ($approved?"success":"danger") }} ">{{ ($approved?"Available":"Pending") }}</small></p>
                                @endforeach
                            </div>
                        @endforeach

                    </div>
                    @endif

                </div>
            </div>

        </div>

    </div>


    <div class="row">
        <div class="col-12">
            <div class="card-box">
                <ul class="nav nav-tabs tabs-bordered">

                    <li class="nav-item">
                        <a href="#purchase_orders" data-toggle="tab" aria-expanded="false" class="nav-link active">
                            PURCHASE ORDERS <span class="badge badge-primary">{{ $vendor->purchase_orders->count() }}</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="#transactions" data-toggle="tab" aria-expanded="false" class="nav-link">
                            TRANSACTIONS <span class="badge badge-primary">{{ $vendor_transactions->count() }}</span>
                        </a>
                    </li>

                    @can('users.payment.vendor')
                    <li class="nav-item">
                        <a href="#vendor_credits" data-toggle="tab" aria-expanded="false" class="nav-link ">
                            VENDOR CREDITS
                        </a>
                    </li>
                    @endcan

                </ul>

                <div class="tab-content">

                    <div class="tab-pane active" id="purchase_orders" style="max-height: 500px; overflow: scroll;">

                        <table id="txns-datatable" class="table datatable">
                            <thead class="">
                            <tr>
                                <th></th>
                                <th>Date</th>
                                <th>Order#</th>
                                <th>Total</th>
                                <th>Payments</th>
                                <th>Balance</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($vendor->purchase_orders as $purchase_order)

                                <tr>
                                    <td><span class="badge badge-{{ status_class($purchase_order->status) }}">{{ ucwords($purchase_order->status) }}</span></td>
                                    <td>{{ $purchase_order->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                                    <td><a href="{{ route('purchase-orders.show', $purchase_order) }}">{{ $purchase_order->ref_number }}</a></td>
                                    <td>{{ display_currency($purchase_order->total) }}</td>
                                    <td>
                                        @if($purchase_order->transactions->count())
                                            <span  class="text-danger">({{ display_currency($purchase_order->transactions->sum('amount')) }})</span>
                                        @endif
                                    </td>
                                    <td>{{ display_currency($purchase_order->balance) }}</td>
                                    <td>
                                        @can('users.payment.vendor')
                                        @if($purchase_order->balance)
                                        <a href="{{ route('vendors.payments', ['vendor'=>$vendor, 'purchase_order'=>$purchase_order]) }}" class="btn btn-primary btn-sm">Pay</a>
                                        @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach

                            </tbody>

                            <tfoot>
                            <tr>
                                <th colspan="3"></th>
                                <th>{{ display_currency($vendor->purchase_orders->sum('total')) }}</th>
                                <th><span class="text-danger">({{ display_currency($total_payments) }})</span></th>
                                <th colspan="2">{{ display_currency($vendor->purchase_orders->sum('balance')) }}</th>
                            </tr>
                            </tfoot>

                        </table>


                    </div>

                    <div class="tab-pane" id="transactions" style="max-height: 500px; overflow: scroll;">

                        <table id="txns-datatable" class="table datatable">
                            <thead class="">
                            <tr>
                                <th>Txn #</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Ref#</th>
                                <th>Memo</th>
                                <th></th>
                                <th>Order#</th>
                                <th>By</th>
                                @can('users.payment.vendor')<th></th>@endcan
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($vendor_transactions as $transaction)
                                {{--                                    {{ dump($transaction) }}--}}
                                <tr>
                                    <td>{{ $transaction->id }}</td>
                                    <td>{{ $transaction->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                                    <td>{{ ($transaction->location?$transaction->location->name:"--") }}</td>
                                    <td>
                                        @if($transaction->amount < 0)
                                            <span class="text-danger">({{ display_currency(abs($transaction->amount)) }})</span>
                                        @elseif($transaction->amount==0)
                                            --
                                        @else
                                            {{ display_currency($transaction->amount) }}
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($transaction->type) }}</td>
                                    <td>{{ ($transaction->payment_method) }}</td>
                                    <td>{{ ($transaction->ref_number) }}</td>
                                    <td>{!! nl2br($transaction->memo??"") !!}</td>
                                    <td>
                                        @if($transaction->signature)
                                            <a class="btn btn-secondary btn-sm btn-success" href="{{ route('vendors.transactions.paid-signature', [$vendor, $transaction]) }}">View</a>
                                        @elseif(strtolower($transaction->payment_method) == 'cash')
                                            <a class="btn btn-secondary btn-sm" href="{{ route('vendors.transactions.paid-signature', [$vendor, $transaction]) }}">Cash {{ ($transaction->amount < 0 ?"Receipt":"Pickup") }}</a>
                                        @endif
                                    </td>
                                    <td style="white-space: nowrap">
                                        @if($transaction->purchase_order)
                                            <a href="{{ route('purchase-orders.show', $transaction->purchase_order) }}">{{ $transaction->purchase_order->ref_number }}</a>

                                        @elseif($transaction->children->count() == 1 && $transaction->children->first()->purchase_order)
                                            <a href="{{ route('purchase-orders.show', $transaction->children->first()->purchase_order) }}">{{ $transaction->children->first()->purchase_order->ref_number }}</a>

                                        @elseif($transaction->children->count() == 1 || $transaction->children->count() > 1)
                                            <a href="javascript:void(0)" data-toggle="modal" data-target=".vendor-payment-{{ $transaction->id }}"><i class=" mdi mdi-link-variant"></i> Details ({{ $transaction->children->count() }})</a>

                                            @include('_partials/_vendor_payment_details_modal', ['transaction'=>$transaction])

                                        @endif
                                    </td>
                                    <td>{{ $transaction->user->name }}</td>
                                    @can('users.payment.vendor')
                                        <td>
                                            @if($transaction->location && !$transaction->location->trashed())
                                                <form action="{{ route('vendors.payments.destroy', [$vendor, $transaction]) }}" method="POST">
                                                    {{ method_field('DELETE') }}
                                                    {{ csrf_field() }}
                                                    <button type="submit" class="btn btn-sm btn-danger waves-effect" onclick="return confirm('Are you sure you want to delete this transaction?')"><i class="ion-trash-a"></i></button>
                                                </form>
                                            @else
                                                <button type="submit" class="btn btn-sm btn-secondary waves-effect" data-toggle="tooltip" data-placement="left" title="" data-original-title="Location is inactive. Unable to delete transaction"><i class="ion-trash-a"></i></button>
                                            @endif
                                        </td>
                                    @endcan
                                </tr>

                            @endforeach

                            </tbody>

                            <tfoot>
                            <tr>
                                <th colspan="3"></th>
                                <th>{{ display_currency($vendor_transactions->sum('amount')) }}</th>
                                <th colspan="8"></th>
                            </tr>
                            </tfoot>
                        </table>

                    </div>

                    @can('users.payment.vendor')
                    <div class="tab-pane " id="vendor_credits">

                        <div class="row">
                            <div class="col-xl-8 col-12">
                                {{ Form::open(['url'=>route('vendors.credits.store', $vendor), 'class'=>'prevent_double_click']) }}

                                @include('_vendor_credit_form')

                                {{ Form::close() }}
                            </div>
                        </div>
                    </div>
                    @endcan

                </div>
            </div>
        </div>
    </div>

@endsection