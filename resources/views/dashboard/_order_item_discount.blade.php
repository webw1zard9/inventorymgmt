
<div class="row">
    <div class="col-lg-12">
        <div class="card-box">

            <h4 class="text-dark  header-title m-t-0 m-b-30">Order Item Discount Approval</h4>

            <div class="table-responsive">
                <table id="user-datatable" class="table">

                    <thead>
                    <tr>
                        <th>Location</th>
                        <th>Order Date</th>
                        <th>Order#</th>
                        <th>Customer</th>
                        <th>Sales Rep</th>
                        <th>Batch Name</th>
                        <th>Qty</th>
                        <th>Cost</th>
                        <th>Sale Price</th>
                        <th>Requested Sale Price</th>
                        <th>Unit Discount</th>
                        <th>Total Discount</th>
                        <th>Notes</th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>

                    <tbody>

                    @unless($order_batch_locations->count())
                        <tr>
                            <td colspan="13"><p>No Data</p></td>
                        </tr>

                    @endunless

                        @foreach($order_batch_locations as $batch_location)

                            <tr>
                                <td>{{ $batch_location->location->name }}</td>
                                <td>{{ $batch_location->order_detail->sale_order->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                                <td><a href="{{ route('sale-orders.show', $batch_location->order_detail->sale_order->id) }}">{{ $batch_location->order_detail->sale_order->ref_number }}</a></td>
                                <td>{{ $batch_location->order_detail->sale_order->customer->name }}</td>
                                <td>{{ ($batch_location->order_detail->sale_order->sales_rep?$batch_location->order_detail->sale_order->sales_rep->name:"--") }}</td>
                                <td>{{ $batch_location->name }}</td>
                                <td>{{ $batch_location->quantity * -1 }} {{ $batch_location->batch->uom }}</td>
                                <td>{{ display_currency($batch_location->batch->unit_price) }}</td>
                                <td>{{ display_currency($batch_location->batch->suggested_unit_sale_price) }}</td>
                                <td>{{ display_currency($batch_location->order_detail->unit_sale_price) }}</td>
                                <td class="text-{{ ($batch_location->order_detail->line_unit_discount<0?"danger":"success") }}">{{ display_currency($batch_location->order_detail->line_unit_discount) }}</td>
                                <td class="text-{{ ($batch_location->order_detail->line_discount<0?"danger":"success") }}">{{ display_currency($batch_location->order_detail->line_discount) }}
                                    {{--<small>{{ number_format($batch_location->order_detail->line_discount_pct, 2) }}%</small>--}}
                                </td>
                                <td>{!! nl2br($batch_location->order_detail->sale_order->notes) !!}</td>
                                <td>
                                    {{ Form::open(['class'=>'form-horizontal', 'url'=>route('batch-location.approve-discount', $batch_location->id)]) }}
                                    {{ method_field('PUT') }}
                                    {{ Form::hidden('price_approved', 1) }}
                                    <button class="btn btn-success waves-effect waves-light" type="submit">Approve</button>

                                    {{ Form::close() }}

                                </td>
                                <td>
                                    {{ Form::open(['class'=>'form-horizontal', 'url'=>route('batch-location.reject-discount', $batch_location->id)]) }}
                                    {{ method_field('PUT') }}
                                    {{ Form::hidden('price_approved', 1) }}
                                    <button class="btn btn-danger waves-effect waves-light" type="submit" onclick="return confirm('Rejection will set the sale price back to the suggested sale price for this item.')">Reject</button>

                                    {{ Form::close() }}

                                </td>
                            </tr>

                        @endforeach

                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>