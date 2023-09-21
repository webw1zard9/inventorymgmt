
<div class="row">
    <div class="col-2"><h5><a
                    href="{{ route('purchase-orders.show', $purchase_order) }}">{{ $purchase_order->ref_number }}</a>
        </h5></div>
    <div class="col-2"><h5>Order Total: {{ display_currency($purchase_order->total) }}</h5></div>
    <div class="col-2"><h5>Balance: {{ display_currency($purchase_order->balance) }}</h5></div>
    <div class="col-3"><h5>Total Inventory
            Value: {{ (Auth::user()->hasLocation()?"--":display_currency($purchase_order->remaining_inventory_value)) }}</h5>
    </div>
    <div class="col-2"><h5>Currently Owed: {{ display_currency($purchase_order->total_owed) }}</h5></div>

</div>


<table class="table">
    <tr>
        <td style="background-color: #f9f9f9"><a href="javascript:void(0)" data-toggle="collapse"
                                                 data-target="#target-{{ $purchase_order->id }}"
                                                 class="accordion-toggle">Batches <i class=" mdi mdi-arrow-expand"></i>
            </a></td>
    </tr>
    <tr>
        <td style="padding: 0 !important;">
            <div class="accordian-body collapse" id="target-{{ $purchase_order->id }}">
                <table class="table" style="background-color: #f9f9f9">
                    <thead>
                    <tr>
                        <th scope="col">SKU</th>
                        <th scope="col" colspan="2">Batch Name</th>
                        <th scope="col">Unit Cost</th>
                        <th scope="col">Purchased</th>
                        <th scope="col">Nest Inventory</th>
                        <th scope="col">Nest Value</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($purchase_order->batches as $batch)
                        <tr>
                            <td>{{ $batch->ref_number }}</td>
                            <td colspan="2"><a href="{{ route('batches.show', $batch->id) }}">{{ $batch->name }}</a>
                            </td>
                            <td>{{ display_currency($batch->original_unit_price) }}</td>
                            <td>{{ ($batch->units_purchased) }} {{ $batch->uom }}</td>
                            <td>
                                {{ floatval($batch->available_for_allocation) }} {{ $batch->uom }}
                                {{--                {{ ($batch->inventory) }} {{ $batch->uom }}--}}
                            </td>
                            <td>{{ display_currency($batch->available_for_allocation * ($batch->getOriginal('unit_price'))) }}</td>
                        </tr>
                    @endforeach

                    </tbody>
                </table>
            </div>
        </td>
    </tr>
</table>

@if($purchase_order->location_cost_owed->count())
    <table class="table">
        <thead>
        <tr>
            <th>Location Name</th>
            <th>Inventory Value</th>
            <th>Reconciled Cost</th>
            <th>Pending Cost</th>
            <th>Sold Cost</th>
            <th>Total Paid</th>
            <th>Balance</th>
        </tr>
        </thead>
        <tbody>
        @foreach($purchase_order->location_cost_owed->sortKeys() as $location_name => $location_balances)

            <tr>

                <td>{{ $location_name }}</td>
                <td>

                    @if(isset($location_balances['remaining_items']) && count($location_balances['remaining_items']))

                        {{ display_currency($location_balances['remaining_cost']) }}

                        {{--                        <span type="button" class="badge badge-info waves-effect waves-light font-16">--}}
                        <a href="javascript:void(0)" class="font-14 text-light" data-toggle="modal"
                           data-target=".bl-{{ Str::slug($location_name) }}-remaining-{{ $purchase_order->id }}">
                            <i class="fa fa-question-circle"></i>
                        </a>
                        {{--                            {{ count($location_balances['remaining_items']) }}--}}
                        {{--                        </span>--}}

                        <div class="modal fade bl-{{ Str::slug($location_name) }}-remaining-{{ $purchase_order->id }}"
                             tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;"
                             aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×
                                        </button>
                                        <h4 class="modal-title" id="mySmallModalLabel">Inventory Value Remaining</h4>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>SKU</th>
                                                <th>Name</th>
                                                <th>Unit/Cost</th>
                                                <th>Subtotal</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($location_balances['remaining_items'] as $remaining_item)
                                                <tr>
                                                    <td>{{ $remaining_item['batch_sku'] }}</td>
                                                    <td>{{ $remaining_item['batch_name'] }}</td>
                                                    <td>{{ $remaining_item['remaining_units'] }} {{ $remaining_item['uom'] }}
                                                        @ {{ display_currency($remaining_item['cost']) }}</td>
                                                    <td>{{ display_currency($remaining_item['subtotal_cost']) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                            <tfoot>
                                            <th colspan="3"></th>
                                            <th>{{ display_currency(collect($location_balances['remaining_items'])->sum('subtotal_cost')) }}</th>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div><!-- /.modal-content -->
                            </div><!-- /.modal-dialog -->
                        </div>

                    @else
                        --
                    @endif


                </td>
                <td>

                    @if(!empty($location_balances['reconciled_cost_details']))
                        {{ display_currency($location_balances['reconciled_cost']) }}

                        <a href="javascript:void(0)" class="font-14 text-light" data-toggle="modal"
                           data-target=".bl-{{ Str::slug($location_name) }}-reconciled-{{ $purchase_order->id }}">
                            <i class="fa fa-question-circle"></i>
                        </a>

                        <div class="modal fade bl-{{ Str::slug($location_name) }}-reconciled-{{ $purchase_order->id }}"
                             tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;"
                             aria-hidden="true">
                            <div class="modal-dialog modal-lg" style="max-width: 75% !important;">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×
                                        </button>
                                        <h4 class="modal-title" id="myLargeModalLabel">Reconciled Inventory</h4>
                                    </div>
                                    <div class="modal-body">
                                        <table class="table">
                                            <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>SKU</th>
                                                <th>Qty</th>
                                                <th>Type</th>
                                                <th>Loss</th>
                                                <th>Notes</th>
                                                <th>By</th>
                                                <th>Date</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($location_balances['reconciled_cost_details'] as $reconciled_item)
                                                <tr>
                                                    <td>{{ $reconciled_item->batch_name }}</td>
                                                    <td>
                                                        <a href="{{ route('batches.show', $reconciled_item->batch_id) }}">{{ $reconciled_item->batch_sku }}</a>
                                                    </td>
                                                    <td>{{ $reconciled_item->quantity_transferred }} {{ $reconciled_item->batch_uom }}</td>
                                                    <td>{{ $reconciled_item->quantity_transferred < 0 ? "Gain" : "Loss" }}</td>
                                                    <td>{{ display_currency($reconciled_item->inventory_loss) }}</td>
                                                    <td>{{ $reconciled_item->notes }}</td>
                                                    <td>{{ $reconciled_item->user->name }}</td>
                                                    <td>{{ $reconciled_item->created_at->format(config('inventorymgmt.date_time_format')) }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                            <tfoot>
                                            <th colspan="4"></th>
                                            <th>{{ display_currency(collect($location_balances['reconciled_cost_details'])->sum('inventory_loss')) }}</th>
                                            <th colspan="3"></th>
                                            {{--                                            <th></th>--}}
                                            </tfoot>
                                        </table>
                                    </div>
                                </div><!-- /.modal-content -->
                            </div><!-- /.modal-dialog -->
                        </div>

                    @else
                        --
                    @endif

                </td>
                <td>
                    {{--                    {{ dd($location_balances) }}--}}
                    @if(!empty($location_balances['pending_cost']))
                        {{ display_currency($location_balances['pending_cost']) }}

                        @include('accounting.partials._payables_cost_modal', [
                            'title'=>'Pending',
                            'all_order_details'=>$location_balances['pending_order_details'],
                            'total_cost'=>$location_balances['pending_cost'],
                            'total_rev'=>$location_balances['pending_revenue']
                            ])
                    @else
                        --
                    @endif
                </td>
                <td>
                    @if(!empty($location_balances['delivered_cost']))

                        {{ display_currency($location_balances['delivered_cost']) }}

                        @include('accounting.partials._payables_cost_modal', [
                                'title'=>'Delivered',
                                'all_order_details'=>$location_balances['delivered_order_details'],
                                'total_cost'=>$location_balances['delivered_cost'],
                                'total_rev'=>$location_balances['delivered_revenue']
                                ])
                    @else
                        --
                    @endif
                </td>

                <td>

                    @if(!empty($location_balances['transactions']))

                        {{ display_currency($location_balances['total_paid']) }}

                        <a href="javascript:void(0)" class="font-14 text-light" data-toggle="modal"
                           data-target=".od-{{ Str::slug($location_name) }}-payments-{{ $purchase_order->id }}">
                            <i class="fa fa-question-circle"></i>
                        </a>

                        {{--                        <span type="button" class="badge badge-warning waves-effect waves-light" data-toggle="modal" data-target=".od-{{ $location_name }}-payments-{{ $purchase_order->id }}">Payments ({{ count($location_balances['transactions']) }})</span>--}}

                        <div class="modal fade od-{{ Str::slug($location_name) }}-payments-{{ $purchase_order->id }}" tabindex="-1"
                             role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;"
                             aria-hidden="true">
                            <div class="modal-dialog modal-md">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×
                                        </button>
                                        <h4 class="modal-title" id="mySmallModalLabel">Payments</h4>
                                    </div>
                                    <div class="modal-body">
                                        <ul>
                                            @foreach($location_balances['transactions'] as $transaction)
                                                <li>{{ $transaction->txn_date->format('m/d/Y') }}
                                                    - {{ display_currency($transaction->amount) }}
                                                    - {{ $transaction->user->name }}</li>
                                            @endforeach
                                        </ul>
                                        <h5>Total: {{ display_currency($location_balances['total_paid']) }}</h5>
                                    </div>
                                </div><!-- /.modal-content -->
                            </div><!-- /.modal-dialog -->
                        </div>
                    @else
                        --
                    @endif

                </td>
                <td>
                    @if($location_balances['total_owed'] > 0)
                        {{--                        {{ route('purchase-orders.show', [$purchase_order, "a"=>$location_balances['total_owed'], 'lid'=>!empty($location_balances['location_id'])?$location_balances['location_id']:0]) }}--}}
                        <button class="btn btn-sm btn-success">{{ display_currency($location_balances['total_owed']) }}</button>
                    @elseif($location_balances['total_owed'] < 0)
                        <button class="btn btn-sm btn-danger">
                            {{ display_currency($location_balances['total_owed']) }}
                        </button>
                    @else
                        {{ display_currency($location_balances['total_owed']) }}
                    @endif
                </td>
            </tr>
            {{--@endforeach--}}

            {{--@endforeach--}}
        @endforeach

        </tbody>

    </table>
@endif

<hr>
