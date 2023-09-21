@extends('layouts.app')


@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">
                <h3 class="title">{{ $batch->category->name }}: {{ $batch->name }}</h3>

                <h4><a href="{{ route('batches.show', $batch->id) }}">{{ $batch->ref_number }}</a></h4>

                <p>
                    Available For Sale: {{ $batch->available_for_sale }} {{ $batch->uom }}<br>
                </p>
            </div>
        </div>
    </div>



    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                        <tr>
                            <th>#</th>

                            <th>Date</th>
                            <th>SO</th>
                            <th>Status</th>
                            <th>Customer</th>
                            <th>Internal Notes</th>
                            <th>Qty</th>
                            <th>Cost</th>
                            <th>Price</th>
                            <th>Order Total</th>
                            {{--<th>Order Margin</th>--}}
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($sale_orders as $sale_order)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $sale_order->txn_date->format('m/d/Y') }}</td>
                                <td><a href="{{ route('sale-orders.show', $sale_order->id) }}">{{ $sale_order->ref_number }}</a></td>
                                <td><span class="badge badge-{{ status_class($sale_order->status) }}">{!! display_status($sale_order->status) !!}</span></td>
                                <td>{{ $sale_order->customer->name }}</td>
                                <td>{{ $sale_order->notes }}</td>
                                <td>{{ $sale_order->order_details->sum('units') }} {{ $batch->uom }}</td>

                                <td>{{ display_currency($sale_order->order_details->first()->unit_cost) }}</td>
                                <td>{{ display_currency($sale_order->order_details->first()->unit_sale_price) }}</td>
                                <td>{{ display_currency($sale_order->total) }}</td>
                                {{--<td class="text-{{ ($sale_order->margin > 0?'success':'danger') }}">{{ display_currency($sale_order->margin) }} <small>({{ $sale_order->margin_pct }}%) <i class="ion-arrow-{{ ($sale_order->margin > 0?'up':'down') }}-c"></i> </small></td>--}}
                            </tr>
                        @endforeach


                        </tbody>

                        <tfoot>
                            <tr>
                                <td colspan="6"></td>
                                <td colspan="3">{{ $sale_orders->pluck('order_details')->collapse()->sum('units') }} {{ $batch->uom }}</td>
                                <td>{{ display_currency($sale_orders->sum('total')) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        </div>

    </div>


@endsection