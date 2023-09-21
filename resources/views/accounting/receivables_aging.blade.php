@extends('layouts.app')


@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <h5 class="hidden-print">Aging Receivables:</h5>

                <div class="row">

                @foreach($sale_orders->groupBy('age') as $age => $sale_orders_by_age)

                    <div class="col-lg-3">
                    <h3>{{ $age }} - {{ display_currency($sale_orders_by_age->sum('balance')) }}</h3>

                    <ul>

                    @foreach($sale_orders_by_age->groupBy('customer.name') as $customer_name => $sale_order_by_customer)

                            <li><strong>{{ $customer_name }} - {{ display_currency($sale_order_by_customer->sum('balance')) }}</strong>

                            <ul>
                        @foreach($sale_order_by_customer as $sale_order)

                                    <li>Order Date: {{ $sale_order->txn_date->format('m/d/Y') }} | Due Date: {{ $sale_order->due_date?$sale_order->due_date->format('m/d/Y'):$sale_order->txn_date->format('m/d/Y') }} <a href="{{ route('sale-orders.show', $sale_order) }}">{{ $sale_order->ref_number }}</a> {{ display_currency($sale_order->balance) }}</li>

                        @endforeach
                            </ul>
                        </li>

                    @endforeach

                    </ul>

                    </div>
                @endforeach

                </div>

            </div>
        </div>
    </div>

@endsection