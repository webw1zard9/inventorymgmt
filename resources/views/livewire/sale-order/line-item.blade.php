
<tr class="{{ ($order_detail->needs_approval?"table-warning":"") }}">
    <td>
{{--{{ dump($order_detail) }}--}}
        {{ Form::open(['url'=>'#', 'class'=>'item_update_form ', 'wire:submit.prevent="update"']) }}

        <div class="row">
            <div class="col-12 col-lg-5 col-xl-6 mb-2">

                <h4><small><strong>SKU:</strong></small> <a href="{{ route('batches.show', $order_detail->batch->id) }}">{{ $order_detail->batch->ref_number }}</a></h4>

{{--                <h5 class="text-muted" style="margin: 0 0 5px"></h5>--}}

                <h4 style="margin: 0px 0px 5px 0px;"><span class="text-muted">{{ $order_detail->batch->category->name }}{{ ($order_detail->batch->brand?": ".$order_detail->batch->brand->name:"") }}</span> - {{ $order_detail->sold_as_name }}</h4>

                <div>
                    <span style="white-space: nowrap">
                    @can('batches.show.cost')
                    <span class="mr-1"><strong>Cost:</strong> {{ display_currency($order_detail->unit_cost) }}</span>
                    @endcan
                    <span class="mr-1"><strong>Price:</strong> {{ display_currency($order_detail->batch->suggested_unit_sale_price) }}</span>
                    <span class="mr-1"><strong>Stock:</strong>
                        @if($order_detail->batch->track_inventory)
                            <span class="text-{{ (!$available_inventory?"danger":"success") }}">{{ $available_inventory }} {{ $order_detail->batch->uom }}</span>
                        @else
                            <i>Unlimited</i>
                        @endif
                    </span>
                    @if($order_detail->line_unit_discount<0)
                        <br><span class="text-danger">Discount: {{ display_currency($order_detail->line_discount) }}</span>
                    @endif
                    </span>

                </div>

            </div>
            <div class="col-12 col-lg-7 col-xl-6">
                @if($order_detail->sale_order->canAddItems() && !empty($order_detail->batch) && is_null($order_detail->units_accepted))

                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6">

                            <div class="input-group mb-2">
                                <input wire:model.defer="order_detail.units" type="number" class="form-control" autocomplete="off">
                                <span class="input-group-addon">{{ $order_detail->batch->uom }}</span>
                            </div>
{{--                            <p>Available: <span class="text-{{ (!$order_detail->batch->location_quantity_available?"danger":"success") }}">{{ round($order_detail->batch->location_quantity_available) }} {{ $order_detail->batch->uom }}</span></p>--}}
                        </div>

                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="input-group mb-2">
                                <span class="input-group-addon">$</span>
                                <input wire:model.defer="order_detail.unit_sale_price" type="text" class="form-control" autocomplete="off">
                            </div>

                            <button type="submit" class="btn btn-primary waves-effect waves-light pull-right ml-1">Save</button>

                            @if (session()->has('od-success'))
                                <i  class="mdi mdi-check-circle font-18 text-success pull-right"></i>
                            @endif

                            <div wire:loading wire:target="update" class="pull-right">
                                <x-loading class="la-sm pt-2" />
                            </div>

                        </div>

                    </div>

                @else
                    <h5 style="margin-top: 24px">{{ $order_detail->units }} {{ (!empty($order_detail->batch)?$order_detail->batch->uom:'') }}
                    @ {{ display_currency($order_detail->unit_sale_price) }}</h5>
                @endif

            </div>

        </div>

        @error('od-error')
        <div class="row mt-2">
            <div class="col-12">
                <div class="alert alert-danger">
                    <span class="error">{{ $message }}</span>
                </div>
            </div>
        </div>
        @enderror

        {{ Form::close() }}
    </td>

    <td>
        <h4 class="order_detail_subtotal_{{ Str::slug($order_detail->sale_order->status) }}">
            {{ display_currency($order_detail->subtotal) }}
        </h4>
    </td>

    @if( ! $order_detail->sale_order->isHold())

        <td style="width: 150px; white-space: nowrap">
            @if(!empty($order_detail->batch) && $order_detail->sale_order->isReadyToPack() && $order_detail->notAccepted())
                <div class="input-group has{{ display_fulfillment_status($order_detail) }}">
                    <input wire:keyup.debounce.500ms="fulfillItem" wire:model="order_detail.units_fulfilled" type="text" class="form-control form-control{{ display_fulfillment_status($order_detail) }} fulfill-item" style="min-width: 75px">
                    <span class="input-group-addon">{{ $order_detail->batch->uom }}</span>
                </div>
            @else
                @if(!is_null($order_detail->units_accepted))
                    <span class="order_detail_fulfilled_{{ Str::slug($order_detail->sale_order->status) }} badge badge-pill badge-success badge-item-fulfilled">{{ $order_detail->units_accepted }} {{ $order_detail->batch->uom }}</span><br>
                @endif
            @endif

            @if($order_detail->fulfill_activity_log && $order_detail->units_fulfilled)
                <small>By {{ $order_detail->fulfill_activity_log->causer->name }}</small>
            @endif
        </td>

    @endif

    @if($order_detail->sale_order->canAddItems())
        <td style="text-align: right">
            <button wire:click="removeItem" wire:loading.attr="disabled" type="submit" class="btn btn-danger waves-effect waves-light" ><i class=" mdi mdi-delete-forever"></i></button>
            <br>
            <div wire:loading wire:target="removeItem" wire:loading.class="d-flex justify-content-center" class="mt-1">
                <x-loading class="la-sm"/>
            </div>

        </td>
    @endif

</tr>
