@extends('layouts.app')

@section('content')

    <h3>Daily Close Out Report</h3>

    <div class="row">
        <div class="col-lg-12">

            <div class="card-box">

                {{ Form::open(['route' => 'accounting.daily-close-out-report', 'method' => 'get']) }}

               <div class="row">
                   <div class="col-md-5 col-xl-3">
                       <dl class="row col-12">
                           <dt class="col-lg-3 text-lg-right">From:</dt>
                           <dd class="col-lg-9">
                               <input class="form-control" type="date" name="from_delivered_at" value="{{ $from_delivered_at }}">
                           </dd>
                       </dl>
                   </div>
                   <div class="col-md-5 col-xl-3">
                       <dl class="row col-12">
                       <dt class="col-lg-3 text-lg-right">To:</dt>
                       <dd class="col-lg-9">
                           <input class="form-control" type="date" name="to_delivered_at" value="{{ $to_delivered_at }}">
                       </dd>
                       </dl>
                   </div>
                   <div class="col-md-2 col-xl-3">
                       <button type="submit" class="btn btn-primary waves-effect waves-light mr-1">Go</button>
                   </div>

               </div>

                {{ Form::close() }}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">

            <h4 class="pull-left">Proft & Loss</h4>

            <a href="{{ route('accounting.daily-close-out-report-export', [request()->getQueryString()]) }}" class="pull-right btn btn-secondary ">Export Details</a>

            <div class="clearfix"></div>


                @if( ! Auth::user()->hasLocation())
                    <div class="card m-b-20 card-block">
                        <h3 class="mt-0 card-title">All Locations</h3>
                        <div class="table-responsive" data-pattern="priority-columns">
                        <table class="table datatable">
                            <thead class="">
                            <tr>
                                <th>Revenue</th>
                                <th>COG</th>
                                <th>Profit</th>
                            </tr>
                            </thead>
                            <tbody>

                                <tr>
                                    <td>{{ display_currency($locations->sum('total_rev')) }}</td>
                                    <td>{{ display_currency($locations->sum('total_cog')) }}</td>
                                    <td>{{ display_currency($locations->sum('total_profit')) }}</td>
                                </tr>

                            </tbody>
                        </table>
                        </div>
                    </div>
                @endif

                @if($locations->count())
                @foreach($locations as $location)

                <div class="card m-b-20 card-block">
                    <h3 class="mt-0 card-title">{{ $location->name }}</h3>
                    <div class="table-responsive" data-pattern="priority-columns">
                        <table class="table datatable dataTable no-footer">
                        <thead class="">
                        <tr>
                        <th>Sales Rep</th>
                        <th>Revenue</th>
                        <th>COG</th>
                        <th>Profit</th>
                        <th>#</th>
                        </tr>
                        </thead>
                        <tbody>

                        @foreach($location->sale_orders->groupBy('sales_rep.name') as $sales_rep_name => $sale_orders)

                        <tr>
                        <td>{{ $sales_rep_name }}</td>
                        <td>{{ display_currency($sale_orders->sum('total')) }}</td>
                        <td>{{ display_currency($sale_orders->sum('cost')) }}</td>
                        <td>{{ display_currency($sale_orders->sum('total') - $sale_orders->sum('cost')) }}</td>
                        <td>{{ $sale_orders->count() }}</td>
                        </tr>

                        @endforeach

                        <tr>
                        <th>Total</th>
                        <th>{{ display_currency($location->total_rev) }}</th>
                        <th>{{ display_currency($location->total_cog) }}</th>
                        <th>{{ display_currency($location->total_profit) }}</th>
                        <th>{{ $location->total_order_count }}</th>
                        </tr>

                        </tbody>
                        </table>
                    </div>
                </div>
                @endforeach

                @else
                <div class="card m-b-20 card-block">
                <p>No Data</p>
                </div>
                @endif

        </div>

        <div class="col-lg-8">

            <h4>Transactions ({{ $transactions->count() }}) {{ display_currency($transactions->sum('amount')) }}</h4>

            <div class="card-box">

                <div class="row">
                    <div class="col-lg-6 mb-3">

                    </div>
                    <div class="col-lg-6 mb-3">
                        <div id="txn-buttons" class="pull-right"></div>
                    </div>
                </div>


                <table id="txns-datatable" class="table datatable">
                    <thead class="">
                    <tr>
                        <th>Location</th>
                        <th>User</th>
                        <th># Txn</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Crypto Amt</th>
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
                                        {{ display_currency($transactions3->sum('amount')) }}


                                        <div class="modal fade od-{{ Str::slug($location_name.$user_name.$payment_method) }}" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                                        <h4 class="modal-title" id="mySmallModalLabel">{{ $payment_method }} Payments at {{ $location_name }} by {{ $user_name }}</h4>
                                                    </div>
                                                    <div class="modal-body">

                                                        @foreach($transactions3 as $transaction)


                                                            <p>
                                                                {{ $transaction->txn_date->format(config('inventorymgmt.date_format')) }}

                                                                @if($transaction->sale_order)
                                                                    <a href="{{ route('sale-orders.show', $transaction->sale_order) }}">{{ $transaction->sale_order->ref_number }}</a>

                                                                    <span class="badge badge-{{ status_class($transaction->sale_order->status) }}">{{ ucwords($transaction->sale_order->status) }}</span>

                                                                    {{ $transaction->sale_order->customer->name }}
                                                                @else

                                                                    {{ $transaction->journal_transaction->journal->morphed->name }}

                                                                @endif

                                                                {{ display_currency($transaction->amount) }}

                                                                @if( ! in_array($transaction->payment_method, ['Cash','Credit']))
                                                                ({{ $transaction->ref_number }} {{ $transaction->payment_method }})
                                                                @endif

                                                            </p>
                                                            <hr>
                                                        @endforeach

                                                        <h5>Total: {{ display_currency($transactions3->sum('amount')) }}
                                                            @if( ! in_array($transaction->payment_method, ['Cash','Credit']))
                                                            ({{ $transactions3->sum('ref_number') }} {{ $transaction->payment_method }})
                                                            @endif
                                                        </h5>
                                                    </div>
                                                </div><!-- /.modal-content -->
                                            </div><!-- /.modal-dialog -->
                                        </div>


                                    </td>
                                    <td>{{ $payment_method }}</td>
                                    <td>{{ ($transactions3->sum('ref_number')?$transactions3->sum('ref_number'):"") }}</td>
                                </tr>

                            @endforeach

                        @endforeach

                    @endforeach

                    </tbody>

                </table>


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

    </script>

@endsection