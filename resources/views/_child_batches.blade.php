@php
    $indent = 20;
@endphp

@foreach($child_batches as $child_batch)

    <tr>

        <td style="padding-left: {{ $depth+$indent }}px; white-space: nowrap;">
            @if($depth>0)
            <i class=" mdi mdi-subdirectory-arrow-right"></i>
            @endif
            {{ $child_batch->id }}
        </td>
        <td style="padding-left: {{ $depth+$indent }}px;">
            {{ $child_batch->category->name }}: {{ $child_batch->present()->branded_name }}
        </td>
        <td style="padding-left: {{ $depth+$indent }}px;">
            <a href="{{ route('batches.show', $child_batch->id) }}">{{ $child_batch->ref_number }}</a>
        </td>

        <td style="padding-left: {{ $depth+$indent }}px; white-space: nowrap;">
            {{ $child_batch->units_purchased }} {{ $child_batch->uom }} @ {{ display_currency($child_batch->unit_price) }}
            @if($child_batch->uom=='g')
                <br><small>({{ number_format($child_batch->units_purchased/config('inventorymgmt.uom.lb'), 4) }} lb @ {{ display_currency($child_batch->unit_price * config('inventorymgmt.uom.lb')) }})</small>
            @endif
        </td>
        <td>
            @if($child_batch->order_details->count())

                @foreach($child_batch->order_details as $order_detail)

                    @if(is_null($order_detail->units_accepted) || $order_detail->units_accepted > 0)

                        {{ $order_detail->sale_order->txn_date->format(config('inventorymgmt.date_format')) }}<br>
                        {{ $order_detail->sale_order->customer->name }} <a href="{{ route('sale-orders.show', $order_detail->sale_order) }}">{{ $order_detail->sale_order->ref_number }}</a>
                        {{--Lic# {{ ($order_detail->sale_order->destination_license?$order_detail->sale_order->destination_license->number:'--') }}--}}
                        <br>
                        <p>{{ nl2br($order_detail->sale_order->notes) }}</p>
                        @if($order_detail->units_accepted>0)
                            <i><small class="text-success">Sold</small></i> <br>{{ $order_detail->units_accepted }}
                        @else
                            <i><small class="text-warning">Pending</small></i> <br>{{ $order_detail->units }}
                        @endif

                        {{ $child_batch->uom }} @ {{ display_currency($order_detail->unit_sale_price) }}<br>

                        @if($order_detail->order_detail_returned->count())
                            <i><small class="text-danger">Return</small></i> <br>

                            @foreach($order_detail->order_detail_returned as $order_detail_returned)
                                {{ $order_detail_returned->units_accepted }} {{ $child_batch->uom }} @ {{ display_currency($order_detail_returned->unit_sale_price) }}<br>
                            @endforeach

                        @endif

                        <div>
                            Unit margin:
                            <span class="text-{{ ($order_detail->unit_margin > 0 ? 'success' : 'danger') }}">
                                {{ display_currency($order_detail->unit_margin) }}
                            </span>
                        </div>

                        <div>
                            Total Profit:

                            <span class="text-{{ ($order_detail->margin_actual > 0 ? 'success' : 'danger') }}">
                                {{ display_currency($order_detail->margin_actual) }}
                                @if($order_detail->margin_actual)
                                <small>({{ $order_detail->margin_pct }}%) <i class="ion-arrow-{{ ($order_detail->margin_actual > 0?'up':'down') }}-c"></i> </small>
                                @endif
                            </span>
                        </div>
                    <div>
                        Accepted: {{ $order_detail->weight_accepted_grams }} g ({{ $order_detail->weight_accepted_pounds }} lb)<br>
                        {{--{{ dump($child_batch) }}--}}
                        Pending: {{ $order_detail->weight_pending_grams }} g ({{ $order_detail->weight_pending_pounds }} lb)

                    </div>
<hr>
                        @else

                        <span>--</span>

                    @endif

                @endforeach
            @else
                --
            @endif

        </td>
        <td style="padding-left: {{ $depth+$indent }}px; white-space: nowrap;">{{ $child_batch->inventory }} {{ $child_batch->uom }}</td>

    </tr>

    @if (!empty($child_batch->children_batches))

        @include('_child_batches', ['child_batches'=>$child_batch->children_batches, 'depth'=>($depth+$indent)])

    @endif

    @if( ! empty($child_batch->created_batch) )
        <tr>
            <td style="padding-left: {{ $depth+$indent }}px; white-space: nowrap;">
                <i class=" mdi mdi-subdirectory-arrow-right"></i> Created: {{ $child_batch->created_batch->id }}
            </td>
            <td style="padding-left: {{ $depth+$indent }}px; white-space: nowrap;">{{ $child_batch->created_batch->category->name }}: {{ $child_batch->created_batch->present()->branded_name }}</td>
            <td style="padding-left: {{ $depth+$indent }}px; white-space: nowrap;"><a href="{{ route('batches.show', $child_batch->created_batch->id) }}">{{ $child_batch->created_batch->ref_number }}</a></td>
            <td style="padding-left: {{ $depth+$indent }}px; white-space: nowrap;">{{ $child_batch->created_batch->units_purchased }} {{ $child_batch->created_batch->uom }}</td>
        </tr>

        {{--<ul>--}}
        {{--@each('_child_batches', $child_batch->created_batch, 'child_batch')--}}
        {{--</ul>--}}
    @endif

    @if( ! $depth)

    <tr style="background-color: #efefef;">
        <td>
            <strong>{{ $child_batch->id }}</strong>
        </td>
        <td>
            <strong>{{ $child_batch->category->name }}: {{ $child_batch->name }}</strong>
        </td>
        <td></td>
        <td>
            <strong>Total Cost: {{ display_currency($child_batch->cost) }}</strong><br>
            <strong>Start: {{ $child_batch->units_purchased }} {{ $child_batch->uom }}
                ({{ $child_batch->units_purchased_grams }} g)
            </strong><br>

            <strong>Sold: {{ $child_batch->weight_accepted_pounds }} lb
                ({{ $child_batch->weight_accepted_grams }} g)</strong><br>

            <strong>Packaged: {{ $child_batch->packaged_weight_pounds }} lb
                ({{ $child_batch->packaged_weight_grams }} g)</strong><br>

            <strong>Diff: </strong><br>
            <strong>Inventory: {{ $child_batch->inventory }} {{ $child_batch->uom }}
                ({{ number_format($child_batch->inventory, 4) }} g)</strong><br>
        </td>
        <td>
            <strong>Total Revenue: {{ display_currency($child_batch->revenue2) }}<br>
                Total Profit: <span class="text-{{ ($child_batch->margin2 > 0?'success':'danger') }}">{{ display_currency($child_batch->margin2) }}

                    @if($child_batch->revenue2 > 0)
                    ({{ number_format(($child_batch->margin2 / $child_batch->revenue2)*100, 2) }}%)
                    @endif
                </span></strong>
            <br>
            <div>
                Accepted: {{ $child_batch->weight_accepted_grams }} g ({{ $child_batch->weight_accepted_pounds }} lb)<br>
                Pending: {{ $child_batch->weight_pending_grams - $child_batch->weight_accepted_pounds }} g

            </div>
        </td>
        <td></td>
    </tr>

    @endif

@endforeach