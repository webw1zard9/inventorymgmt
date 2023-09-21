@extends('layouts.app')


@section('content')

    <div class="row">
        <div class="col-lg-12">

            <h4>Report Period: {{ Carbon\Carbon::parse($from)->format(config('inventorymgmt.date_format')) }} - {{ Carbon\Carbon::parse($to)->format(config('inventorymgmt.date_format')) }}</h4>

            <div class="card-box">

                @include('_partials._date_range')

{{--                {{ Form::open(['url' => url()->current(), 'method' => 'get']) }}--}}

{{--                @include('accounting._date_period')--}}

{{--                <div class="col-md-2 col-xl-3">--}}
{{--                    <button type="submit" class="btn btn-primary waves-effect waves-light mr-1">Run Report</button>--}}
{{--                </div>--}}

{{--                {{ Form::close() }}--}}

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">

            <h4>Transactions ({{ $transactions->count() }}) {{ display_currency($transactions->sum('amount')) }}</h4>
            <div class="card-box">
                <div class="row">

                    <div class="col-lg-12 mb-3">
                        <div id="txn-buttons" class="pull-right"></div>
                    </div>
                </div>

            <div class="table-responsive">
                <table id="txns-datatable" class="table datatable">
                    <thead class="">
                    <tr>
                        <th>Location</th>
                        <th>User</th>
                        <th># Txn</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Crypto Amount</th>
                        <th>Avg. Coin Value</th>
                    </tr>
                    </thead>
                    <tbody>

                    @foreach($transactions->groupBy('user.name') as $user_name => $transactions)
                        {{--{{ dd($transactions->first()) }}--}}
                        @foreach($transactions->groupBy('location.name') as $location_name => $transactions2)

                            @foreach($transactions2->groupBy('payment_method') as $payment_method => $transactions3)

                                <tr>
                                    <td>{{ $location_name }}</td>
                                    <td>{{ $user_name }}</td>
                                    <td>{{ $transactions3->count() }}
                                        <span type="button" class="badge badge-default waves-effect waves-light pull-right" data-toggle="modal" data-target=".od-{{ Str::slug($location_name.$user_name.$payment_method) }}">Details</span>
                                    </td>
                                    <td>
                                        {{ display_currency($transactions3->sum('total_amount')) }}


                                        <div id="custom-width-modal" class="modal fade od-{{ Str::slug($location_name.$user_name.$payment_method) }}" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;" aria-hidden="true">
                                            <div class="modal-dialog modal-lg" style="max-width: 90% !important;">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                                        <h4 class="modal-title" id="mySmallModalLabel">{{ $payment_method }} Payments at {{ $location_name }} by {{ $user_name }}</h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="table-responsive">
                                                        <table id="txns-datatable" class="table datatable">
                                                            <thead class="">
                                                            <tr>
                                                                <th>ID</th>
                                                                <th>Payment Date</th>
                                                                <th>Sales Order</th>
                                                                <th>Status</th>
                                                                <th>Customer</th>
                                                                <th>Amount Credited</th>
                                                                <th>Txn Fee</th>

                                                                {{--@if( ! in_array($payment_method, ['Cash','Credit']))--}}
                                                                <th>Payment Rcv'd</th>
                                                                <th>Crypto Rcv'd</th>
                                                                <th>Coin Value</th>
                                                                {{--@endif--}}

                                                                <th>Memo</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>

                                                        @foreach($transactions3 as $transaction)

                                                            <tr>
                                                                <td>{{ $transaction->id }}</td>
                                                                <td>{{ $transaction->txn_date->format(config('inventorymgmt.date_format')) }}</td>

                                                                <td>
                                                                    @if($transaction->sale_order)
                                                                        <a href="{{ route('sale-orders.show', $transaction->sale_order) }}">{{ $transaction->sale_order->ref_number }}</a>
                                                                    @endif
                                                                </td>

                                                                <td>
                                                                    @if($transaction->sale_order)
                                                                        <span class="badge badge-{{ status_class($transaction->sale_order->status) }}">{{ ucwords($transaction->sale_order->status) }}</span>
                                                                    @endif
                                                                </td>
                                                                <td style="white-space: nowrap">
                                                                    @if($transaction->sale_order)
                                                                        {{ $transaction->sale_order->customer->name }}
                                                                    @else
                                                                        {{ ($transaction->journal_transaction->journal && $transaction->journal_transaction->journal->morphed?$transaction->journal_transaction->journal->morphed->name:"--") }}
                                                                    @endif
                                                                </td>
                                                                <td>{{ display_currency($transaction->amount) }}</td>
                                                                <td>{{ ($transaction->txn_fee?display_currency($transaction->txn_fee):"--") }}</td>
                                                                <td>{{ display_currency($transaction->amount + $transaction->txn_fee?:0) }}</td>

                                                                {{--@if( ! in_array($transaction->payment_method, ['Cash','Credit']))--}}
                                                                <td style="white-space: nowrap">
                                                                    @if($transaction->ref_number)
                                                                    {{ $transaction->ref_number }} {{ $transaction->payment_method }}
                                                                    @endif
                                                                </td>
                                                                <td style="white-space: nowrap">
                                                                    @if($transaction->ref_number)
                                                                    {{ display_currency($transaction->amount / $transaction->ref_number) }} {{ $transaction->payment_method }}
                                                                    @endif
                                                                </td>
                                                                {{--@endif--}}

                                                                <td>
                                                                    {!! nl2br($transaction->memo) !!}
                                                                </td>

                                                            </tr>

                                                        @endforeach

                                                            </tbody>

                                                            <tfoot>
                                                                <tr>
                                                                    <th>Totals:</th>
                                                                    <th></th>
                                                                    <th></th>
                                                                    <th></th>
                                                                    <th></th>
                                                                    <th>{{ display_currency($transactions3->sum('amount')) }}</th>
                                                                    <th>{{ display_currency($transactions3->sum('txn_fee')) }}</th>
                                                                    <th style="white-space: nowrap">{{ display_currency($transactions3->sum('amount') + $transactions3->sum('txn_fee')) }}</th>
                                                                    <th style="white-space: nowrap">
                                                                        @if($transactions3->sum('ref_number'))
                                                                        {{ $transactions3->sum('ref_number') }} {{ $transaction->payment_method }}
                                                                        @endif
                                                                    </th>
                                                                    <th></th>
                                                                    <th></th>
                                                                </tr>

                                                            </tfoot>

                                                        </table>
                                                        </div>
                                                    </div>
                                                </div><!-- /.modal-content -->
                                            </div><!-- /.modal-dialog -->
                                        </div>


                                    </td>
                                    <td>{{ $payment_method }}</td>
                                    <td>
                                        @if($transactions3->sum('ref_number'))
                                            {{ $transactions3->sum('ref_number') }} {{ $payment_method }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($transactions3->sum('ref_number'))
                                       ~ {{ display_currency($transactions3->sum('amount') / $transactions3->sum('ref_number')) }} {{ $payment_method }}
                                        @endif
                                    </td>
                                </tr>

                            @endforeach

                        @endforeach

                    @endforeach

                    </tbody>

                </table>
            </div>

            </div>
        </div>
    </div>

@endsection


@section('css')

    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css">

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
    <script src="{{ asset('plugins/datatables/buttons.colVis.min.js') }}"></script>

    <script src="{{ asset('plugins/moment/min/moment.min.js') }}"></script>
    <script src="//cdn.datatables.net/plug-ins/1.10.16/sorting/datetime-moment.js"></script>

    <script type="text/javascript">

        $(document).ready(function() {

            var date = new Date();

            $.fn.dataTable.moment('MM/DD/YYYY');

            var table1 = $('#txns-datatable').DataTable({
                lengthChange: true,
                paging: true,
                order: [[ 0, "desc" ]],
                displayLength: 25,
                buttons: [
                    {
                        extend: 'excelHtml5',
                        title: 'transactions-'+date.mmddyy()
                    }
                ],
                columnDefs: [ {
                    "targets": [0],
                    "orderable": false
                } ]
            });

            table1.buttons().container().appendTo('#txn-buttons');

        });

        Date.prototype.mmddyy = function() {
            var yyyy = this.getFullYear().toString();
            var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
            var dd  = this.getDate().toString();
            return (mm[1]?mm:"0"+mm[0])+"-"+(dd[1]?dd:"0"+dd[0])+"-"+yyyy;
            // return yyyy + "/" + (mm[1]?mm:"0"+mm[0]) + "/" + (dd[1]?dd:"0"+dd[0]); // padding
        };

        function sendRequest(from, to, custom) {
            var url = window.location.href.split('?')[0];
            window.location = url+'?'+(custom?'preset=custom&':'')+'from=' + from + '&to=' + to;
        }

    </script>

@endsection