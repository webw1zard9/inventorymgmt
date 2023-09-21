<tr class="{{ ($order_detail->needs_approval?"table-warning":"") }}">

    <td>
        {{ Form::open(['url'=>route('order-details.update', $order_detail), 'class'=>'item_update_form prevent_double_click', 'data-order_detail_id'=>$order_detail->id]) }}
        {{ method_field('PUT') }}
        {{ Form::hidden('cog', 1) }}

        <div class="row">
        <div class="col-12 col-lg-5 col-xl-6">

        @if($saleOrder->isHold() && is_null($order_detail->units_accepted))
            <div class="row">
                <div class="input-group mb-2 col-12">
                    <input type="text" class="form-control" name="sold_as_name" value="{{ $order_detail->sold_as_name }}">
                </div>
            </div>
        @else
            {{ $order_detail->sold_as_name?:$order_detail->batch->name }}
        @endif

            <div class="row">
                <div class="col-12">
                    <p>
                        @if( ! empty($order_detail->batch))
                            <a href="{{ route('batches.show', $order_detail->batch->id) }}">{{ $order_detail->batch->ref_number }}</a><br>
                        @endif

                        @if(!empty($order_detail->batch))
                            {{ $order_detail->batch->category->name }}{{ ($order_detail->batch->brand?": ".$order_detail->batch->brand->name:"") }}
                            <br>
                        @endif

                        @level(60)
                        <strong>Cost:</strong> {{ display_currency($order_detail->unit_cost) }}<br>
                        @endlevel
                        <strong>Price:</strong> {{ display_currency($order_detail->batch->suggested_unit_sale_price) }}

                    </p>
                </div>
            </div>

        </div>
        <div class="col-12 col-lg-7 col-xl-6">
        @if(!empty($order_detail->batch) && $saleOrder->isHold() && is_null($order_detail->units_accepted))

            <div class="row">
{{--                col-12 col-md-12 col-lg-6 col-xl-5--}}
                <div class="col-lg-6 col-md-6 col-sm-6">

                    @if($order_detail->batch->wt_based)
                        {!! display_inventory($order_detail->batch) !!}

                        {{ Form::hidden('units', $order_detail->units) }}

                    @else
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="units" value="{{ $order_detail->units }}" >
                            <span class="input-group-addon">{{ $order_detail->batch->uom }}</span>
                        </div>
                        {{ Form::hidden('original_units', $order_detail->units) }}
                    @endif

                </div>
                <div class="col-lg-6 col-md-6 col-sm-6">
                    <div class="input-group mb-2">
                        <span class="input-group-addon">$</span>
                        <input type="text" class="form-control" name="unit_sale_price" value="{{ display_currency_no_sign($order_detail->unit_sale_price) }}" >
                    </div>
                    {{ Form::hidden('original_unit_sale_price', $order_detail->unit_sale_price) }}
                </div>

                <div class="col-12 text-right">
                    <button type="submit" class="btn btn-primary waves-effect waves-light ml-1">Save</button>
                </div>
            </div>

        @else
            {{ $order_detail->units }} <small>{{ (!empty($order_detail->batch)?$order_detail->batch->uom:'') }}</small>
            @ {{ display_currency($order_detail->unit_sale_price) }}
        @endif

        </div>

        </div>

        {{ Form::close() }}
    </td>

    <td>
        @if($order_detail->line_unit_discount<0)
            <span class="text-danger"> {{ display_currency($order_detail->line_discount) }}</span>
        @else
            --
        @endif
    </td>



    <td>{{ display_currency($order_detail->subtotal) }}</td>

    @if($saleOrder->status !== 'hold')

    <td style="width: 150px; white-space: nowrap">
        @if(!empty($order_detail->batch) && $saleOrder->status == 'ready to pack' && $order_detail->notAccepted())

            <div class="input-group has{{ display_fulfillment_status($order_detail) }} mb-2">
                <input type="text" class="form-control form-control{{ display_fulfillment_status($order_detail) }} fulfill-item" name="oid-{{ $order_detail->id }}" value="{{ $order_detail->units_fulfilled }}">
                <span class="input-group-addon">{{ $order_detail->batch->uom }}</span>
            </div>

        @else

            @if(!is_null($order_detail->units_accepted))
                <span class="badge badge-pill badge-{{ ($order_detail->units_accepted != $order_detail->units?"warning":"success") }} badge-item-fulfilled">{{ $order_detail->units_accepted }} {{ $order_detail->batch->uom }}</span>

                @if(($saleOrder->isHold() || $saleOrder->isReadyToPack()))
                    <a href="{{ route('sale-orders.undo-fulfillment', [$saleOrder, $order_detail]) }}"><i class="font-16 mdi mdi-undo-variant"></i></a>
                @endif

                @if($order_detail->fulfill_activity_log)
                <span><br>by {{ $order_detail->fulfill_activity_log->causer->name }}</span>
                @endif
            @endif

        @endif
    </td>

    @endif

    <td>

        @if($saleOrder->isHold())
            {{ Form::open(['class'=>'form-horizontal pull-right', 'url'=>route('sale-orders.remove-item', [$saleOrder->id, $order_detail->id])]) }}
            <button type="submit" class="btn btn-danger waves-effect waves-light" onclick="return confirm('Are you sure you want to remove from order?')">X</button>
            {{ Form::close() }}
        @endif

    </td>
</tr>