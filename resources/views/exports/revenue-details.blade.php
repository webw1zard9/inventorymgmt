<table>
    <thead>
    <tr>
        <th>Location</th>
        <th>Sale order#</th>
        <th>Order Date</th>
        <th>Delivered Date</th>
        <th>Sales Rep</th>
        <th>Batch Name</th>
        <th>Qty Sold</th>
        <th>Unit Price</th>
        <th>Discount</th>
        <th>COG</th>
        <th>Markup</th>
        <th>Category</th>
        <th>UOM</th>
    </tr>
    </thead>
    <tbody>
    @foreach($locations as $location)

        @foreach($location->sale_orders as $sale_order)

            @foreach($sale_order->order_details as $order_detail)

                <tr>
                    <td>{{ $location->name }}</td>
                    <td>{{ $sale_order->ref_number }}</td>
                    <td>{{ $sale_order->txn_date->format('m/d/Y') }}</td>
                    <td>{{ $sale_order->delivered_at->format('m/d/Y') }}</td>
                    <td>{{ $sale_order->sales_rep->name }}</td>
                    <td>{{ $order_detail->sold_as_name }}</td>
                    <td>{{ $order_detail->units_accepted }}</td>
                    <td>{{ display_currency($order_detail->unit_sale_price, 2, 0, "") }}</td>
                    <td>{{ display_currency($order_detail->batch->suggested_unit_sale_price - $order_detail->unit_sale_price, 2, 0, "") }}</td>
                    <td>{{ display_currency($order_detail->unit_cost, 2, 0, "") }}</td>
                    <td>{{ display_currency($order_detail->unit_sale_price - $order_detail->unit_cost, 2, 0, "") }}</td>
                    <td>{{ $order_detail->batch->category->name }}</td>
                    <td>{{ $order_detail->batch->uom }}</td>
                </tr>

            @endforeach
        @endforeach
    @endforeach
    </tbody>
</table>