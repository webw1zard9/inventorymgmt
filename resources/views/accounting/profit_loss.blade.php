@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <h4>Report Period: {{ Carbon\Carbon::parse($from)->format(config('inventorymgmt.date_format')) }} - {{ Carbon\Carbon::parse($to)->format(config('inventorymgmt.date_format')) }}</h4>

            <div class="card-box">

{{--                {{ Form::open(['url' => url()->current(), 'method' => 'get']) }}--}}

                @include('_partials._date_range')

{{--                <div class="col-md-2 col-xl-3">--}}
{{--                    <button type="submit" class="btn btn-primary waves-effect waves-light mr-1">Run Report</button>--}}
{{--                </div>--}}

{{--                {{ Form::close() }}--}}

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">

            <h4 class="pull-left">Profit & Loss</h4>

            <a href="{{ route('accounting.profit_loss_details_export') }}" class="pull-right btn btn-secondary ">Export Details</a>

            <div class="clearfix"></div>

            @if($locations->count() > 1 ||$nest_reconciliations->count() )
                <div class="card m-b-20 card-block">
                    <h3 class="mt-0 card-title">All Locations</h3>
                    <div class="table-responsive" data-pattern="priority-columns">
                        <table class="table datatable">
                            <thead class="">
                            <tr>
                                <th>Sales</th>
                                <th>Discounts</th>
                                <th>Total Income</th>
                                @role('admin')
                                <th>COG</th>
                                <th>Gross Profit</th>
                                <th>Gross Profit %</th>
                                @endrole
                            </tr>
                            </thead>
                            <tbody>

                            <tr>
                                <td>{{ display_currency($locations->sum('total_order')) }}</td>
                                <td><a href="{{ route('accounting.discounts_export') }}">
                                    {{ display_currency($locations->sum('total_discount')) }}
                                    <small class="text-dark">{{ ($locations->sum('total_order')?number_format(($locations->sum('total_discount') / $locations->sum('total_order'))*100, 2):"0") }}%</small>
                                    <i class="font-16 mdi mdi-file-export"></i></a>
                                </td>
                                <td>{{ display_currency($locations->sum('total_rev')) }}</td>

                                @role('admin')
                                <td>{{ display_currency($locations->sum('total_cog') + $nest_reconciliations->sum('inventory_loss')) }}</td>
                                <td>{{ display_currency($locations->sum('total_profit') - $nest_reconciliations->sum('inventory_loss')) }}</td>
                                <td>{{ ($locations->sum('total_rev')?number_format((($locations->sum('total_profit') - $nest_reconciliations->sum('inventory_loss')) / $locations->sum('total_rev')) * 100, 2):"0") }}%</td>
                                @endrole
                            </tr>

                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($nest_reconciliations->count())
                <div class="card m-b-20 card-block">
                    <h3 class="mt-0 card-title">Nest</h3>
                    <div class="table-responsive" data-pattern="priority-columns">
                        <table class="table datatable">
                            <thead class="">
                            <tr>
                                <th></th>
                                @role('admin')
                                <th>COG</th>
                                @endrole
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>Reconciliations <a href="{{ route('batches.reconcile-log', ['filters'=>$reconciliation_filters->merge(['location_id'=>0])->toArray()]) }}" class="font-14 text-light"><i class="fa fa-question-circle"></i></a></td>
                                @role('admin')
                                <td>{{ display_currency($nest_reconciliations->sum('inventory_loss')) }}</td>
                                @endrole
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
                                    <th>Sales</th>
                                    <th>Discounts</th>
                                    <th>Total Income</th>

                                    @role('admin')
                                    <th>COG</th>
                                    <th>Gross Profit</th>
                                    <th>Gross Profit %</th>
                                    @endrole

                                    <th>#</th>

                                </tr>
                                </thead>
                                <tbody>

                                @foreach($location->sale_orders->groupBy('sales_rep.name') as $sales_rep_name => $sale_orders)

                                    <tr>
                                        <td>{{ $sales_rep_name }}</td>
                                        <td>{{ display_currency($sale_orders->sum('subtotal')) }}</td>
                                        <td>
                                            @if($sale_orders->sum('discount'))
                                            {{ display_currency($sale_orders->sum('discount')) }} <small class="text-dark">{{ ($sale_orders->sum('subtotal')?number_format(($sale_orders->sum('discount')/$sale_orders->sum('subtotal')) * 100, 2):"0") }}%</small>
                                            @else
                                            --
                                            @endif
                                        </td>
                                        <td>{{ display_currency($sale_orders->sum('total')) }}</td>

                                        @role('admin')
                                        <td>{{ display_currency($sale_orders->sum('cost')) }}</td>
                                        <td>{{ display_currency($sale_orders->sum('total') - $sale_orders->sum('cost')) }}</td>
                                        <td>{{ ($sale_orders->sum('total')?number_format((($sale_orders->sum('total') - $sale_orders->sum('cost')) / $sale_orders->sum('total')) * 100, 2):"0") }}%</td>
                                        @endrole

                                        <td>{{ $sale_orders->count() }}</td>
                                    </tr>

                                @endforeach

                                @if($location->total_reconciliation_cost)
                                <tr>
                                    <td>Reconciliations <a href="{{ route('batches.reconcile-log', ['filters'=>$reconciliation_filters->merge(['location_id'=>$location->id])->toArray()]) }}" class="font-14 text-light"><i class="fa fa-question-circle"></i></a></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    @role('admin')
                                    <td>{{ display_currency($location->total_reconciliation_cost) }}</td>
                                    <td>{{ display_currency($location->total_reconciliation_profit) }}</td>
                                    <td></td>
                                    @endrole
                                    <td></td>
                                </tr>
                                @endif

                                <tr>
                                    <th>Total</th>
                                    <th>{{ display_currency($location->total_order) }}</th>
                                    <th>
                                        @if($location->total_discount)
                                            <a href="{{ route('accounting.discounts_export', ['location_id'=>$location->id]) }}">
                                            {{ display_currency($location->total_discount) }}
                                            <i class="font-16 mdi mdi-file-export"></i></a>
                                            </a>
                                        @endif
                                    </th>
                                    <th>{{ display_currency($location->total_rev) }}</th>
                                    @role('admin')
                                    <th>{{ display_currency($location->total_cog) }}</th>
                                    <th>{{ display_currency($location->total_profit) }}</th>
                                    <td>{{ ($location->total_rev?number_format(($location->total_profit/$location->total_rev)*100,2):0) }}%</td>
                                    @endrole

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
    </div>

@endsection

@section('js')

    <script type="text/javascript">

        function sendRequest(from, to, preset) {
            var url = window.location.href.split('?')[0];
            window.location = url+'?'+(preset?'preset='+preset+'&':'')+'from=' + from + '&to=' + to;
        }

    </script>

@endsection