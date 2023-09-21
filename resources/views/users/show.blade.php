@extends('layouts.app')

@section('content')

    @if($user->can_edit_super_user)
        @can('users.edit')
            <a href="{{ route('users.edit', $user->id) }}" class="btn btn-primary waves-effect waves-light m-b-10">Edit</a>
        @endcan
    @endif

    <div class="row">
        <div class="col-lg-5 col-xl-4">

            <div class="card-box">
                <div class="member-card">

                    <div class="text-center ">
                        <h1 class="m-b-5">{{ $user->name }}</h1>
                    </div>

                    <dl class="row">
                        @if($user->hasRole(['customer']))

                            <dt class="col-5 text-right">Available Credit:</dt>
                            <dd class="col-7"><span class="badge badge-success" style="font-size: 18px" >{{ display_currency($user->available_balance) }}</span></dd>

                            <dt class="col-5 text-right">Prepaid Inventory:</dt>
                            <dd class="col-7">{{ display_currency($user->prepaidInventory()) }}</dd>

                            <dt class="col-5 text-right">Order Balances:</dt>
                            <dd class="col-7">{{ display_currency($user->sale_orders->sum('balance')) }}</dd>

                            @if($user->customer_sales_reps->count())
                                <dt class="col-5 text-right">Sales Rep:</dt>
                                <dd class="col-7">
                                    @foreach($user->customer_sales_reps as $sales_rep)
                                        <a href="{{ route('users.show', $sales_rep->id) }}">{{ $sales_rep->name }}</a><br>
                                    @endforeach
                                </dd>
                            @endif

                        @endif

                            <dt class="col-5 text-right">Role:</dt>
                            <dd class="col-7">{{ $user->roles->first()->name }}</dd>

                        @if($user->hasRole(['salesrep']))

                            <dt class="col-5 text-right">Customers:</dt>
                            <dd class="col-7">
                                @foreach($user->sales_rep_customers as $customer)
                                    <a href="{{ route('users.show', $customer->id) }}">{{ $customer->name }}</a><br>
                                @endforeach
                            </dd>

                        @endif

                </dl>

            </div> <!-- end card-box -->


        </div> <!-- end col -->

        </div>

        @if($user->hasRole(['customer']))
        <div class="col-lg-7 col-xl-8">
                <div class="card-box">
                    <ul class="nav nav-tabs tabs-bordered">

                        <li class="nav-item">
                            <a href="#sale_orders" data-toggle="tab" aria-expanded="false" class="nav-link active">
                                ORDERS  <span class="badge badge-primary">{{ $sale_orders->count() }}</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#transactions" data-toggle="tab" aria-expanded="false" class="nav-link">
                                TRANSACTIONS  <span class="badge badge-primary">{{ $all_customer_transactions->count() }}</span>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="#receive_payment" data-toggle="tab" aria-expanded="false" class="nav-link ">
                                PAYMENTS
                            </a>
                        </li>

                    </ul>

                    <div class="tab-content">

                        <div class="tab-pane active" id="sale_orders" style="height: 500px; overflow: scroll;">

                            <table id="txns-datatable" class="table datatable">
                                <thead class="">
                                <tr>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Order#</th>
                                    <th>Status</th>
                                    <th>Sales Rep</th>
                                    <th>Total</th>
                                    <th>Balance</th>
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($sale_orders as $sale_order)

                                    <tr>
                                        <td>{{ $sale_order->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                                        <td>{{ $sale_order->location->name }}</td>
                                        <td><a href="{{ route('sale-orders.show', $sale_order) }}">{{ $sale_order->ref_number }}</a></td>
                                        <td><span class="badge badge-{{ status_class($sale_order->status) }}">{{ ucwords($sale_order->status) }}</span></td>
                                        <td>{{ $sale_order->sales_rep->name }}</td>
                                        <td>{{ display_currency($sale_order->total) }}</td>
                                        <td>{{ display_currency($sale_order->balance) }}</td>
                                    </tr>
                                @endforeach

                                </tbody>

                                <tfoot>
                                <tr>
                                    <th colspan="5"></th>
                                    <th>{{ display_currency($sale_orders->sum('total')) }}</th>
                                    <th>{{ display_currency($sale_orders->sum('balance')) }}</th>
                                    {{--<th>{{ display_currency($all_customer_transactions->sum('amount')) }}</th>--}}
                                </tr>
                                </tfoot>

                            </table>


                        </div>

                        <div class="tab-pane" id="transactions" style="height: 500px; overflow: scroll;">

                            <table id="txns-datatable" class="table datatable">
                                <thead class="">
                                <tr>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Method</th>
                                    <th>Crypto Amt</th>
                                    <th>Memo</th>
                                    <th>Order#</th>
                                    <th>By</th>
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($all_customer_transactions as $transaction)
                                    {{--                                    {{ dump($transaction) }}--}}
                                    <tr>
                                        <td>{{ $transaction->created_at->format(config('inventorymgmt.date_format')) }}</td>
                                        <td>{{ ($transaction->location?$transaction->location->name:"--") }}</td>
                                        <td>{{ display_currency($transaction->amount) }}</td>
                                        <td>{{ ucfirst($transaction->type) }}</td>
                                        <td>{{ ($transaction->payment_method) }}</td>
                                        <td>{{ ($transaction->ref_number) }}</td>
                                        <td>{!! nl2br($transaction->memo) !!}</td>
                                        <td>
                                            @if($transaction->sale_order)
                                                <a href="{{ route('sale-orders.show', $transaction->sale_order) }}">{{ $transaction->sale_order->ref_number }}</a>
                                            @endif
                                        </td>
                                        <td>{{ $transaction->user->name }}</td>
                                    </tr>
                                @endforeach

                                </tbody>

                            </table>

                        </div>

                        <div class="tab-pane " id="receive_payment">

                            {{ Form::open(['url'=>route('customers.payment', $user), 'class'=>'prevent_double_click']) }}

                            @include('_payment_form')

                            {{ Form::close() }}

                        </div>

                    </div>
                </div>
        </div> <!-- end col -->
        @endif

    </div>
    <!-- end row -->


@endsection