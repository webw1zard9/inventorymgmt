
@foreach($customers as $customer)

    <div class="card m-b-20">
        <div class="card-header collapsed" id="heading-{{ $tab }}-{{ $customer->id }}" data-toggle="collapse" data-target="#collapse-{{ $tab }}-{{ $customer->id }}" style="cursor: pointer">
            <h5>{{ $customer->name }}: {{ display_currency($customer->outstanding_balance) }}</h5>
        </div>

        <div id="collapse-{{ $tab }}-{{ $customer->id }}" class="card-block collapse">

            <table class="table table-hover">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">SO Date</th>
                    <th scope="col">SO #</th>
                    {{--<th scope="col">Sales Rep</th>--}}
                    <th scope="col">Aging</th>
                    <th scope="col">Orig. Amount</th>
                    <th scope="col">Balance</th>
                    {{--<th scope="col">Payments</th>--}}
                </tr>
                </thead>
                <tbody>

                @foreach($customer->sale_orders as $sale_order)

                    <tr>
                        <th scope="row">{{ $loop->iteration }}</th>
                        <td>{{ $sale_order->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                        <td><a href="{{ route('sale-orders.show', $sale_order->id) }}">{{ $sale_order->ref_number }}</a></td>
{{--                        <td>{{ (!empty($sale_order->sales_rep)?$sale_order->sales_rep->name:'--') }}</td>--}}
                        <td>{{ $sale_order->txn_date->diffForHumans() }}</td>
                        <td>{{ display_currency($sale_order->total) }}</td>
                        <td>{{ display_currency($sale_order->balance) }}</td>
                        {{--<td><a href="{{ route('sale-orders.show', $sale_order->id) }}">{{ display_currency($sale_order->transactions->sum('amount')) }} ({{ $sale_order->transactions->count() }})</a></td>--}}
                    </tr>

                @endforeach

                </tbody>
            </table>


        </div>
    </div>

@endforeach