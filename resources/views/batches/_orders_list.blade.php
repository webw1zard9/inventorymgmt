<div class="table-responsive">
    <table class="table table-hover table-striped">
        <thead>
        <tr>
            <th>Date</th>
            <th>SO</th>
            <th>Status</th>
            <th>Location</th>
            <th>Customer</th>
            <th>Sales Rep</th>
{{--            <th>Internal Notes</th>--}}
            @can('batches.show.cost')
            <th>Cost</th>
            @endcan
            <th>Qty</th>
            <th>Price</th>
            <th>Order Total</th>
        </tr>
        </thead>
        <tbody>

        @foreach($orders as $sale_order)

            @foreach($sale_order->order_details as $order_detail)

                <tr>
                    <td>{{ $sale_order->txn_date->format('m/d/Y') }}</td>
                    <td><a href="{{ route('sale-orders.show', $sale_order->id) }}">{{ $sale_order->ref_number }}</a></td>
                    <td><span class="badge badge-{{ status_class($sale_order->status) }}">{!! display_status($sale_order->status) !!}</span></td>
                    <td>{{ $sale_order->location->name }}</td>
                    <td>{{ $sale_order->customer->name }}</td>
                    <td>{{ $sale_order->sales_rep->name }}</td>
{{--                    <td>{{ Str::limit($sale_order->notes, 15, "...") }}</td>--}}
                    @can('batches.show.cost')
                    <td>{{ display_currency($order_detail->unit_cost) }}</td>
                    @endcan
                    <td>

                        {{ ($order_detail->final_units) }} {{ $batch->uom }}

                        @if(!is_null($order_detail->units_fulfilled))
                            @if(bccomp($order_detail->units, $order_detail->units_fulfilled) == 0)
                                <i class=" font-16 text-success  mdi mdi-check-circle"></i>
                            @else
                                <i class="font-16 text-warning  mdi mdi-alert"></i>
                                <span class="text-warning"><strong> {{ $order_detail->units_fulfilled }} {{ $batch->uom }}</strong></span>
                            @endif
                        @endif

                    </td>
                    <td>{{ display_currency($order_detail->unit_sale_price) }}</td>
                    <td>{{ display_currency($order_detail->unit_sale_price * ($order_detail->final_units)) }}</td>
                </tr>

            @endforeach

        @endforeach


        </tbody>

        <tfoot>
        <tr>
            <th colspan="6"></th>
            @can('batches.show.cost')<th></th>@endcan
            <th>{{ $orders->pluck('order_details')->collapse()->sum('final_units') }} {{ $batch->uom }}</th>
            <th colspan=""></th>
            <th colspan="">{{ display_currency($orders->pluck('order_details')->collapse()->sum('line-item-subtotal')) }}</th>

        </tr>
        </tfoot>
    </table>
</div>