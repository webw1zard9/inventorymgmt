<table>
    <thead>
    <tr>
        <th>Location</th>
        <th>Employee</th>
        <th>Customer</th>
        <th>Sales Rep</th>
        <th>Sale order#</th>
        <th>Order Date</th>
        <th>Delivered Date</th>
        <th>Subtotal</th>
        <th>Discount</th>
        <th>Discount %</th>
        <th>Total</th>
        <th>Discount Applied</th>
        <th>Discount Type</th>
        <th>Discount Description</th>
    </tr>
    </thead>
    <tbody>
        @foreach($sale_orders as $sale_order)

            <tr>
                <td>{{ $sale_order->location->name }}</td>
                <td>{{ $sale_order->user->name }}</td>
                <td>{{ $sale_order->customer->name }}</td>
                <td>{{ $sale_order->sales_rep->name }}</td>
                <td>{{ $sale_order->ref_number }}</td>
                <td>{{ $sale_order->txn_date->format('m/d/Y') }}</td>
                <td>{{ $sale_order->delivered_at->format('m/d/Y') }}</td>
                <td>{{ display_currency($sale_order->subtotal) }}</td>
                <td>{{ display_currency($sale_order->discount*-1) }}</td>
                <td>{{ number_format(($sale_order->discount / $sale_order->subtotal)*100, 2) }}%</td>
                <td>{{ display_currency($sale_order->total) }}</td>
                <td>{{ $sale_order->discount_applied }}</td>
                <td>{{ $sale_order->discount_type }}</td>
                <td>{{ $sale_order->discount_description }}</td>

            </tr>

        @endforeach
    </tbody>
</table>