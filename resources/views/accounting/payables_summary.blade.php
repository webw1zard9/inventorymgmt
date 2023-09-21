@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <div class="card-box">
                <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th></th>
                        <th></th>
                        <th>PO Balance</th>
                        <th>On-Hand Inventory <i class="ion-help-circled" data-toggle="tooltip" data-placement="top" title="" data-original-title="Value of available for sale + held inventory"></i></th>
                        <th>Available Inventory <i class="ion-help-circled" data-toggle="tooltip" data-placement="top" title="" data-original-title="Value of inventory available for sale"></i></th>
                        <th>Hold Orders <i class="ion-help-circled" data-toggle="tooltip" data-placement="top" title="" data-original-title="Value of inventory on 'hold' or 'ready-to-pack'"></i></th>
                        <th>Fulfilled Orders <i class="ion-help-circled" data-toggle="tooltip" data-placement="top" title="" data-original-title="Value of inventory fulfilled, ready-for-delivery."></i></th>
                        <th>Reconciled</th>
                        <th>Total Payable</th>
                        <th></th>
                        <th>Total COGS</th>
                        <th>Paid-to-date</th>
                        <th>Payable</th>
                        <th>Payable Discrepancy</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($payable_data as $vendor_id => $vendor_data)

                        <tr>
                            <td class="payables_summary_td_row"><a href="javascript:void(0)" data-toggle="collapse" data-target="#target-{{$vendor_id}}" class="accordion-toggle"><i class="font-14 ion-plus-round"></i> </a></td>
                            <td><h4><a href="{{ route('vendors.show', $vendor_id) }}">{{ $vendor_data['name'] }}</a></h4></td>
                            <td class="payables_summary_td_row">{{ ($vendor_data['po_balance'] > 0) ? display_currency($vendor_data['po_balance']) : "--" }}</td>

                            <td class="payables_summary_td_row">{{ ( optional($vendor_data['onhand_costs'])->sum() ? display_currency($vendor_data['onhand_costs']->sum()) : "--" ) }}</td>
                            <td class="payables_summary_td_row">{{ ( optional($vendor_data['available_costs'])->sum() ? display_currency($vendor_data['available_costs']->sum()) : "--" ) }}</td>
                            <td class="payables_summary_td_row">{{ ( optional($vendor_data['pending_costs'])->sum() ? display_currency($vendor_data['pending_costs']->sum()) : "--" ) }}</td>
                            <td class="payables_summary_td_row">{{ ( optional($vendor_data['fulfilled_costs'])->sum() ? display_currency($vendor_data['fulfilled_costs']->sum()) : "--" ) }}</td>
                            <td class="payables_summary_td_row">{{ ( optional($vendor_data['current_reconciled_costs'])->sum() ? display_currency($vendor_data['current_reconciled_costs']->sum()) : "--") }}</td>
                            <td class="payables_summary_td_row" style="white-space: nowrap">
                                <a href="{{ route('accounting.vendor_payables', $vendor_id) }}"><strong class="text-primary">{{ (((float)$vendor_data['total_payables']) ? display_currency($vendor_data['total_payables']) : "--") }}</strong> <i class=" mdi mdi-open-in-new"></i></a>

                            </td>
                            <td class="payables_summary_td_row">
                                @if($vendor_data['total_payables'] && $vendor_data['total_payables'] > 0)
                                    <a href="{{ route('vendors.payments', $vendor_id) }}" class="btn btn-sm btn-primary">Make Payment</a>
                                @endif
                            </td>
                            <td class="payables_summary_td_row">{{ (optional($vendor_data['total_cogs'])->sum() ? display_currency($vendor_data['total_cogs']->sum()) : "--") }}</td>
                            <td class="payables_summary_td_row text-danger">{{ (!empty($vendor_data['paid_costs']) ? "(".display_currency($vendor_data['paid_costs']->sum()).")" : "--") }}</td>
                            <td class="payables_summary_td_row">{{ ((float)($vendor_data['payables_check']) ? display_currency($vendor_data['payables_check']) : "--") }}</td>
                            <td class="payables_summary_td_row">{{ (((float)$vendor_data['payables_diff']) ? display_currency($vendor_data['payables_diff']) : "--") }}</td>
                        </tr>

                        <tr class="accordion-body collapse" id="target-{{ $vendor_id }}">

                            <td></td>
                            <td></td>
                            <td></td>

                            @foreach(['onhand_costs','available_costs', 'pending_costs', 'fulfilled_costs', 'current_reconciled_costs', 'space','space', 'total_cogs','paid_costs'] as $column)

                                @if(($column=='space'))
                                    <td></td>
                                    @continue
                                @endif

                                <td>
                                    @if(!empty($vendor_data[$column]))
                                        @foreach($vendor_data[$column] as $location_name => $subtotal)
                                            @if($column=='paid_costs')
                                                <p><strong>{{ $location_name }}:</strong> <span class="text-danger">({{ display_currency($subtotal) }})</span></p>
                                            @else
                                                <p><strong>{{ $location_name }}:</strong> {{ display_currency($subtotal) }}</p>
                                            @endif
                                        @endforeach
                                    @endif
                                </td>

                            @endforeach
                            <td>
                                @if(!empty($vendor_data['total_cogs']))
                                    @foreach($vendor_data['total_cogs'] as $location_name => $subtotal)
                                    <p><strong>{{ $location_name }}:</strong> {{ display_currency($subtotal - ($vendor_data['paid_costs'][$location_name]??0)) }}</p>
                                    @endforeach
                                @endif
                            </td>
                            <td></td>
                        </tr>

                    @endforeach
                    </tbody>

                    <tfoot>
                        <tr>
                            <th></th>
                            <th>Totals</th>
                            <th>{{ display_currency($payable_data->sum('po_balance')) }}</th>
                            <th>{{ display_currency($payable_data->sum('onhand_costs_total')) }}</th>
                            <th>{{ display_currency($payable_data->sum('available_costs_total')) }}</th>
                            <th>{{ display_currency($payable_data->sum('pending_costs_total')) }}</th>
                            <th>{{ display_currency($payable_data->sum('fulfilled_costs_total')) }}</th>
                            <th>{{ display_currency($payable_data->sum('current_reconciled_costs_total')) }}</th>
                            <th>{{ display_currency($payable_data->sum('total_payables')) }}</th>
                            <th></th>
                            <th>{{ display_currency($payable_data->sum('total_cogs_total')) }}</th>
                            <th class="text-danger">({{ display_currency($payable_data->sum('paid_costs_total')) }})</th>
                            <th>{{ display_currency($payable_data->sum('payables_check')) }}</th>
                            <th>{{ display_currency($payable_data->sum('payables_diff')) }}</th>
                        </tr>
                    </tfoot>

                </table>
                </div>
            </div>
        </div>
    </div>

@endsection