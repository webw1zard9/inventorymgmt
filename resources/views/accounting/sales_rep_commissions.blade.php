@extends('layouts.app')


@section('content')
<style>
    @media print {

        .d-print-none {
            display: none !important;
        }
        .d-print-block {
            display: block !important;
        }
    }


</style>
    <div class="row">
        <div class="col-lg-12">
            <h3>Sales Rep Commissions</h3>

            <div class="card-box">

                {{ Form::open(['url'=>route('accounting.sales_rep_commissions'), 'method'=>'GET']) }}

                <div class="row">
                            <div class="col-sm-3 col-lg-2">
                                {{ Form::label('sales_rep_id', 'Sales Rep') }}
                                {{ Form::select("sales_rep_id", [''=>'-- Select Sales Rep --'] + $sales_reps->toArray(), request('sales_rep_id'), ['class'=>'form-control', 'required'=>'required']) }}
                            </div>
                            <div class="col-sm-3 col-lg-2">
                                {{ Form::label('end_date', 'Pay Period End Date') }}
                                <input class="form-control" type="date" name="end_date" value="{{ request('end_date')?: \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                            </div>

                            @if(!empty($sales_commissions) && $sales_commissions->count())
                            <div class="col-lg-1 text-center d-print-none">
                                <strong>-- OR --</strong>
                            </div>

                            <div class="col-lg-2 d-print-none">
                                {{ Form::label('sales_commission_id', 'Commission History') }}
                                {{ Form::select("sales_commission_id", [''=>'-- Previous Sales Comm --'] + $sales_commissions->pluck('pay_period', 'id')->toArray(), request('sales_commission_id'), ['class'=>'form-control']) }}
                            </div>
                            @endif

                            <div class="col-lg-3 d-print-none">
                                <button type="submit" class="btn btn-primary waves-effect waves-light">Go</button>
                            </div>

                    {{--<div class="col-lg-6">--}}
                        {{--@if(!empty($sales_commissions) && $sales_commissions->count())--}}
                        {{--<div class="row">--}}

                            {{--<div class="col-lg-5">--}}
                                {{--{{ Form::select("sales_commission_id", [''=>'-- Previous Sales Comm --'] + $sales_commissions->pluck('pay_period', 'id')->toArray(), request('sales_commission_id'), ['class'=>'form-control']) }}--}}
                            {{--</div>--}}
                            {{--<div class="col-lg-2">--}}
                                {{--<button type="submit" class="btn btn-primary waves-effect waves-light">Go</button>--}}
                            {{--</div>--}}
                        {{--</div>--}}
                        {{--@endif--}}
                    {{--</div>--}}
                </div>

                {{ Form::close() }}
                <hr>
                @if( ! empty($sale_orders) && empty($sales_commission))

                {{ Form::open(['url'=>route('accounting.sales_rep_commissions')]) }}

                {{ Form::hidden('sales_rep_id', request('sales_rep_id')) }}
                {{ Form::hidden('period_start', $start_date->toDateString()) }}
                {{ Form::hidden('period_end', $end_date->toDateString()) }}
                {{ Form::hidden('total_revenue', $sale_orders->sum('subtotal_after_discount'), ['id'=>'total_revenue_value']) }}


                <div class="table-responsive">

                    <p>Pay Period: <strong>{{ $start_date->format('m/d/Y') }} - {{ $end_date->format('m/d/Y') }}</strong></p>
                    <p>Generated Revenue: <strong id="total_revenue">{{ display_currency($sale_orders->sum('subtotal_after_discount')) }}</strong></p>
                    <p>Total Commission: <strong id="total_commission">{{ display_currency($sale_orders->sum('commission')) }}</strong></p>

                    @if($sale_orders->sum('subtotal_after_discount'))
                    <p>Blended Avg. Commission: <strong id="blended_avg">{{ round(($sale_orders->sum('commission')/$sale_orders->sum('subtotal_after_discount'))*100, 2) }}%</strong></p>
                    @endif

                    <table id="review_sales_comm-datatable" class="table table-hover table-striped">

                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>SO#</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Units</th>
                                <th>Sales Rep</th>
                                <th>Subtotal</th>
                                <th>Discount</th>
                                <th>Sub w/Disc</th>
                                <th>Balance</th>
                                <th>Bulk</th>
                                <th>Days</th>
                                <th>Rate</th>
                                <th>Commission</th>
                                {{--<th>Pay</th>--}}
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($sale_orders as $sale_order)

                            <tr>
                                <td>
                                    {{ $sale_order->txn_date->format(config('inventorymgmt.date_format')) }}
                                    {{ Form::hidden("sale_orders[".$sale_order->id."][sale_order_id]", $sale_order->id) }}
                                </td>
                                <td><a href="{{ route('sale-orders.show', $sale_order) }}">{{ $sale_order->ref_number }}</a></td>
                                <td>{{ $sale_order->customer->name }}</td>
                                <td>{{ $sale_order->customer_type }}</td>
                                <td>{{ (!empty($sale_order->units_purchased) ? implode(", ", $units_purchased[$sale_order->id]) : '--') }}</td>
                                <td>{{ ($sale_order->sales_rep?$sale_order->sales_rep->name:'--') }}</td>
                                <td>{{ display_currency($sale_order->subtotal) }}</td>
                                <td>{{ display_currency($sale_order->discount) }}</td>
                                <td>
                                    {{ display_currency($sale_order->subtotal_after_discount) }}
                                    {{ Form::hidden("sale_orders[".$sale_order->id."][subtotal]", $sale_order->subtotal_after_discount, ['id'=>'sale_order_subtotal_'.$sale_order->id]) }}
                                </td>
                                <td>{{ display_currency($sale_order->balance) }}</td>
                                <td>

                                    {{ ($sale_order->bulk_order?'Yes':'No') }}
                                    {{ Form::hidden("sale_orders[".$sale_order->id."][is_bulk_order]", (int)$sale_order->bulk_order) }}
                                </td>
                                <td>{{ $sale_order->days_since_first_order }}</td>
                                <td>
                                    <div class="input-group bootstrap-touchspin d-print-none">
                                        <input id="sale_order_comm_rate_{{ $sale_order->id }}" type="text" value="{{ $sale_order->comm_rate*100 }}" name="sale_orders[{{ $sale_order->id }}][rate]" class="form-control col-3 comm-rate" data-sale_order_id="{{ $sale_order->id }}" style="display: block;">
                                        <span class="input-group-addon bootstrap-touchspin-prefix">%</span>
                                    </div>
                                    <div class="d-none d-print-block">
                                        {{ $sale_order->comm_rate*100 }}%
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group bootstrap-touchspin d-print-none">
                                        <span class="input-group-addon bootstrap-touchspin-prefix">$</span>
                                        <input id="sale_order_comm_{{ $sale_order->id }}" type="text" value="{{ display_currency($sale_order->commission, 2, 0, "") }}" name="sale_orders[{{ $sale_order->id }}][amount]" class="form-control col-6 comm-value" data-sale_order_id="{{ $sale_order->id }}" style="display: block;">
                                    </div>
                                    <div class="d-none d-print-block">
                                        {{ display_currency($sale_order->commission, 2, 0, "") }}
                                    </div>
                                </td>
                                {{--<td>--}}
                                    {{--{{ Form::checkbox('sale_orders['.$sale_order->id.'][pay]', 1, true) }}--}}
                                {{--</td>--}}

                            </tr>
                            @endforeach
                        </tbody>

                    </table>

                    <button type="submit" class="btn btn-primary waves-effect waves-light pull-right d-print-none">Save</button>

                </div>

                {{ Form::close() }}

                @endif

                @if( ! empty($sales_commission))

                    <h3>Sales Commissions</h3>



                    <div class="table-responsive">

                        <p>Entered by: <strong>{{ $sales_commission->user->name }}</strong></p>
                        <p>Sales Rep: <strong id="sales_rep_name">{{ $sales_commission->sales_rep->name }}</strong></p>

                        <p>Pay Period: <strong id="pay_period">{{ $sales_commission->period_start->format('m/d/Y') }} - {{ $sales_commission->period_end->format('m/d/Y') }}</strong></p>
                        <p>Generated Revenue: <strong>{{ display_currency($sales_commission->total_revenue) }}</strong></p>
                        <p>Total Commission: <strong >{{ display_currency($sales_commission->total_commission) }}</strong></p>
                        <p>Blended Avg. Commission: <strong>{{ round(($sales_commission->total_commission/$sales_commission->total_revenue)*100, 2) }}%</strong></p>

                        <div class="row">

                            <div class="col-lg-12 mb-3">
                                <div id="datatable-buttons" class="pull-right"></div>
                            </div>

                        </div>

                        <table id="sales_comm-datatable" class="table table-hover table-striped">

                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>SO#</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Subtotal w/Discount</th>
                                <th>Bulk</th>
                                <th>Rate</th>
                                <th>Commission</th>
                            </tr>
                            </thead>

                            <tbody>
                            @foreach($sales_commission->sales_commission_details as $sales_commission_detail)

                            <tr>
                                <td>{{ $sales_commission_detail->sale_order->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                                <td><a href="{{ route('sale-orders.show', $sales_commission_detail->sale_order) }}">{{ $sales_commission_detail->sale_order->ref_number }}</a></td>
                                <td>{{ $sales_commission_detail->sale_order->customer->name }}</td>
                                <td>{{ $sales_commission_detail->sale_order->customer_type }}</td>
                                <td>{{ display_currency($sales_commission_detail->sale_order->subtotal_after_discount) }}</td>
                                <td>{{ $sales_commission_detail->is_bulk_order?'Yes':'No' }}</td>
                                <td>{{ $sales_commission_detail->rate*100 }} %</td>
                                <td>{{ display_currency($sales_commission_detail->amount) }}</td>
                            </tr>

                            @endforeach

                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Totals</th>
                                    <th></th>
                                    <th></th>
                                    <th></th>
                                    <th>{{ display_currency($sales_commission->total_revenue) }}</th>
                                    <th></th>
                                    <th></th>
                                    <th>{{ display_currency($sales_commission->total_commission) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>



                @endif

            </div>
        </div>
    </div>

@endsection

@section('js')

    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>

    <script src="{{ asset('plugins/datatables/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/responsive.bootstrap4.min.js') }}"></script>

    <script src="{{ asset('plugins/datatables/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/jszip.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/pdfmake.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/vfs_fonts.js') }}"></script>
    <script src="{{ asset('plugins/datatables/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables/buttons.print.min.js') }}"></script>
{{--    <script src="{{ asset('plugins/datatables/buttons.colVis.min.js') }}"></script>--}}

    <script src="{{ asset('plugins/moment/min/moment.min.js') }}"></script>
    <script src="//cdn.datatables.net/plug-ins/1.10.16/sorting/datetime-moment.js"></script>

    <script type="text/javascript">
        jQuery(document).ready(function($) {

            $.fn.dataTable.moment('MM/DD/YYYY');

            // $('[type="date"]').datepicker();

            var table = $('#sales_comm-datatable').DataTable({
                // lengthChange: true,
                paging: false,
                searching: false,
                "order": [[ 0, "desc" ]],
                // "displayLength": 25,
                buttons: [{
                    extend:'excel',
                    filename: $('#sales_rep_name').text()+' Sales Comm',
                    title: 'Sales Commission: '+$('#pay_period').text()+' - '+$('#sales_rep_name').text(),
                },{
                    extend:'pdfHtml5',
                    orientation: 'landscape',
                    footer: true,
                    filename: $('#sales_rep_name').text()+' Sales Comm',
                    title: 'Sales Commission: '+$('#pay_period').text()+' - '+$('#sales_rep_name').text(),
                }]
            });

            table.buttons().container().appendTo('#datatable-buttons');

            $('.comm-rate').change(function() {
                var sale_order_id = $(this).data('sale_order_id');
                var subtotal = $('#sale_order_subtotal_'+sale_order_id).val();
                var new_comm = (subtotal * $(this).val()/100).toFixed(2);
                $('#sale_order_comm_'+sale_order_id).val(new_comm);

                calculate_total_comm();
            });

            $('.comm-value').change(function() {
                var sale_order_id = $(this).data('sale_order_id');
                var subtotal = $('#sale_order_subtotal_'+sale_order_id).val();
                var new_comm_rate = (($(this).val()/subtotal).toFixed(4)*100).toFixed(2);
                $('#sale_order_comm_rate_'+sale_order_id).val(new_comm_rate);

                calculate_total_comm();
            });

            function calculate_total_comm()
            {
                var total_comm = 0;
                $('.comm-value').each(function(){
console.log(parseFloat($(this).val()));
                    total_comm += Number($(this).val());
                    // console.log(total_comm);
                });
                total_comm = total_comm.toFixed(2);

                $('#total_commission').html('$'+total_comm);

                var total_rate =0;
                $('.comm-rate').each(function(){
                    total_rate+=Number($(this).val());
                });

                $('#blended_avg').html((total_rate/$('.comm-rate').length).toFixed(2)+'%');
            }
        });
    </script>

@endsection

