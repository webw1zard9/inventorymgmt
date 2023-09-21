<table>
    <thead>
    <tr>
        <th>Date</th>
        <th>SO#</th>
        <th>Status</th>
        <th>Location</th>
        <th>Customer (Bill-to)</th>
        <th>Sales Rep</th>
        <th>Units</th>
        <th>Total</th>
        <th>Balance</th>
    </tr>
    </thead>
    <tbody>
        @foreach($sale_orders as $sale_order)

            <tr>
                <td>{{ $sale_order->txn_date->format('m/d/Y') }}</td>
                <td><a href="{{ route('sale-orders.show', $sale_order) }}">{{ $sale_order->ref_number }}</a></td>
                <td>{{ ucwords($sale_order->status) }}</td>
                <td>{{ ucwords($sale_order->location->name) }}</td>
                <td>{{ $sale_order->customer->name  }}</td>
                <td>{{ ($sale_order->sales_rep?$sale_order->sales_rep->name:'--') }}</td>
                <td>
                    {{ (!empty($units_purchased[$sale_order->id]) ? implode(", ", $units_purchased[$sale_order->id]) : '--') }}
                </td>
                <td>{{ display_currency($sale_order->total) }}</td>
                <td>{{ display_currency($sale_order->balance) }}</td>
            </tr>

        @endforeach
    </tbody>
</table>