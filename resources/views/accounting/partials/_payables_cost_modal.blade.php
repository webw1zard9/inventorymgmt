@if(count($all_order_details))

    <a href="javascript:void(0)" class="font-14 text-light" data-toggle="modal" data-target=".od-{{ $location_name }}-{{ $title }}-{{ $purchase_order->id }}"><i class="fa fa-question-circle"></i></a>

    <div class="modal fade od-{{ $location_name }}-{{ $title }}-{{ $purchase_order->id }}" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;" aria-hidden="true">
        <div class="modal-dialog modal-lg" style="max-width: 80% !important;">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title" id="mySmallModalLabel">{{ $title }} Cost</h4>
                </div>

                <div class="modal-body">

                    <div class="table-responsive">
                        <table id="{{ $location_name }}-{{ $title }}-orders-datatable" class="table datatable">
                            <thead class="">
                            <tr>
                                <th></th>
                                <th>Date</th>
                                <th>Order#</th>
                                <th>Customer</th>
                                <th>Name</th>
                                <th>Qty</th>
                                <th>Cost</th>
                                <th>Sale Price</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($all_order_details as $order_detail)

                                <tr>
                                    <td><span class="badge badge-{{ status_class($order_detail->sale_order->status) }}">{{ ucwords($order_detail->sale_order->status) }}</span></td>
                                    <td>{{ $order_detail->sale_order->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                                    <td><a href="{{ route('sale-orders.show', $order_detail->sale_order) }}">{{ $order_detail->sale_order->ref_number }}</a></td>
                                    <td>{{ $order_detail->sale_order->customer->name }}</td>
                                    <td>{{ $order_detail->sold_as_name }}</td>
                                    <td>{{ $order_detail->units_accepted?:$order_detail->units }} {{ $order_detail->batch_uom }}</td>
                                    <td>{{ display_currency($order_detail->unit_cost) }}</td>
                                    <td>{{ display_currency($order_detail->unit_sale_price) }}</td>
                                </tr>

{{--                                <div class="row">--}}
{{--                                    <div class="col-6">{{ $order_detail->sold_as_name }}</div>--}}
{{--                                    <div class="col-3">Cost: {{ $order_detail->units_accepted?:$order_detail->units }} {{ $order_detail->batch_uom }} @ {{ display_currency($order_detail->unit_cost) }}</div>--}}
{{--                                    <div class="col-3">Price: {{ $order_detail->units_accepted?:$order_detail->units }} {{ $order_detail->batch_uom }} @ {{ display_currency($order_detail->unit_sale_price) }}</div>--}}
{{--                                </div>--}}
                            @endforeach


                            </tbody>
{{--                            <tfoot>--}}
{{--                            <tr>--}}
{{--                                <th colspan="6"></th>--}}
{{--                                <th>{{ display_currency($total_cost) }}</th>--}}
{{--                                <th>{{ display_currency($total_rev) }}</th>--}}
{{--                            </tr>--}}

{{--                            </tfoot>--}}
                        </table>
                    </div>

{{--                    @foreach(collect($all_order_details)->groupBy('sale_order.ref_number') as $sale_order_ref_number => $order_details)--}}
{{--                        <div class="row mb-2">--}}
{{--                            <div class="col-2"></div>--}}
{{--                            <div class="col-2"></div>--}}
{{--                            <div class="col-2"></div>--}}
{{--                        </div>--}}

{{--                        @foreach($order_details as $order_detail)--}}
{{--                            <div class="row">--}}
{{--                                <div class="col-6"></div>--}}
{{--                                <div class="col-3">Cost:  @ </div>--}}
{{--                                <div class="col-3">Price: {{ $order_detail->units_accepted?:$order_detail->units }} {{ $order_detail->batch_uom }} @ </div>--}}
{{--                            </div>--}}
{{--                        @endforeach--}}
{{--                        <hr>--}}
{{--                    @endforeach--}}

{{--                    <div class="row">--}}
{{--                        <div class="col-6"></div>--}}
{{--                        <div class="col-3"></div>--}}
{{--                        <div class="col-3"></div>--}}
{{--                    </div>--}}

                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div>

@endif