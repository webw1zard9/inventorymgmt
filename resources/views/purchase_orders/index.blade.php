@extends('layouts.app')


@section('content')


    <div class="row mb-3 hidden-print">
        <div class="col-lg-12">

            {{ Form::open(['route' => 'purchase-orders.index', 'method' => 'get']) }}

            <div class="card">

                <div class="card-header cursor-pointer" role="tab" id="filters" >

                    <div class="row">
                        <div class="col-md-3">
                            <a href="#collapse-filters" data-toggle="collapse"><strong><i class="ti-arrow-circle-down"></i> Filters</strong></a>
                            <a href="{{ route('purchase-orders.reset-filters') }}" class="small ml-2">Reset</a>
                        </div>
                        <div class="col-md-5">
                            @if($filters)
                                @foreach($filters as $filter=>$vals)
                                    <span style="margin-right: 15px;">{!! display_filters($filter, $vals, $purchase_orders) !!}</span>
                                @endforeach
                            @endif
                        </div>
                        <div class="col-md-4 text-right">Total: <strong>{{ display_currency($purchase_orders->sum('total')) }}</strong> | Balance: <strong>{{ display_currency($purchase_orders->sum('balance')) }}</strong></div>
                    </div>

                </div>

                <div id="collapse-filters" class=" card-block" role="tabpanel" aria-labelledby="collapse-filters" >

                    <div class="row">
                        <div class="col-lg-2">
                            <dl class="row">
                                <dt class="col-lg-4 text-lg-right">Status:</dt>
                                <dd class="col-lg-8">

                                    @foreach(config('inventorymgmt.po_statuses') as $order_status)
                                        <div class="checkbox">
                                            <input id="checkbox_{{$order_status}}" type="checkbox" name="filters[status][{{$order_status}}]" value="{{ ucwords($order_status) }}" {{ (isset($filters['status']) ? (in_array($order_status, array_keys($filters['status']))?'checked':''):'') }}>

                                            <label for="checkbox_{{$order_status}}">
                                                <span class="badge badge-{{ status_class($order_status) }}">{!! display_status($order_status) !!}</span>
                                            </label>
                                        </div>
                                    @endforeach

                                </dd>
                            </dl>

                        </div>

                        <div class="col-lg-3">
                            <dl class="row">
                                <dt class="col-lg-5 text-lg-right">Date Preset:</dt>
                                <dd class="col-lg-6">

                                    <select id="date_preset" name="filters[date_preset]" class="form-control">
                                        <option value="">- Select -</option>
                                        @for($i=0; $i<=3; $i++)
                                            <option value="{{ \Carbon\Carbon::now()->firstOfMonth()->subMonth($i)->format('m-Y') }}"{{ (isset($filters['date_preset']) ? (\Carbon\Carbon::now()->firstOfMonth()->subMonth($i)->format('m-Y') == $filters['date_preset'] ? 'selected' : '' ) : '') }}>{{ \Carbon\Carbon::now()->firstOfMonth()->subMonth($i)->format('F, Y') }}</option>
                                        @endfor
                                    </select>
                                </dd>
                                <dt class="col-lg-5 text-lg-right"></dt>
                                <dd class="col-lg-6"><p>-- OR --</p>
                                </dd>
                                <dt class="col-lg-5 text-lg-right">Custom Date:</dt>
                                <dd class="col-lg-6">
                                    From:<input class="form-control" type="date" name="filters[from_date]" value="{{ (isset($filters['from_date']) ? $filters['from_date'] : '') }}">
                                    To:<input class="form-control" type="date" name="filters[to_date]" value="{{ (isset($filters['to_date']) ? $filters['to_date'] : '') }}">
                                </dd>
                            </dl>

                        </div>
                        {{--<div class="col-lg-3">--}}
                            {{--<dl class="row">--}}
                                {{--<dt class="col-lg-3 text-lg-right">Funding:</dt>--}}
                                {{--<dd class="col-lg-9">--}}
                                    {{--{{ Form::select("filters[fund_id]", $funds, (!empty($filters['fund_id'])?$filters['fund_id']:null), ['class'=>'form-control', 'placeholder'=>'-- Select --']) }}--}}
                                {{--</dd>--}}

                                {{--<dt class="col-lg-3 text-lg-right">Manifest#:</dt>--}}
                                {{--<dd class="col-lg-9">--}}
                                    {{--{{ Form::text("filters[manifest_no]", (!empty($filters['manifest_no'])?$filters['manifest_no']:null), ['class'=>'form-control', 'placeholder'=>'Manifest #']) }}--}}

                                {{--</dd>--}}
                            {{--</dl>--}}
                        {{--</div>--}}

                        <div class="col-lg-3">
                            <dl class="row">
                                <dt class="col-lg-3 text-lg-right">Vendor:</dt>
                                <dd class="col-lg-9">

                                    <select id="vendor" name="filters[vendor]" class="form-control">
                                        <option value="">- Select -</option>
                                        @foreach($vendors as $vendor)
                                            <option value="{{ $vendor->id }}"{{ (isset($filters['vendor']) ? ($vendor->id == $filters['vendor'] ? 'selected' : '' ) : '') }}>{{$vendor->name}}</option>
                                        @endforeach
                                    </select>

                                </dd>
                            </dl>
                            {{--<dl class="row">--}}
                                {{--<dt class="col-lg-3 text-lg-right">License Type:</dt>--}}
                                {{--<dd class="col-lg-9">--}}

                                    {{--@foreach(['cultivator','distributor','microbusiness'] as $license_type)--}}
                                    {{--<div class="checkbox">--}}
                                        {{--<input id="checkbox_{{$license_type}}" type="checkbox" name="filters[license_type][{{$license_type}}]" value="{{ ucwords($license_type) }}" {{ (isset($filters['license_type']) ? (in_array($license_type, array_keys($filters['license_type']))?'checked':''):'') }}>--}}

                                        {{--<label for="checkbox_{{$license_type}}">--}}
                                            {{--<span class="badge badge-{{ status_class($license_type) }}">{!! display_status($license_type) !!}</span>--}}
                                        {{--</label>--}}
                                    {{--</div>--}}
                                    {{--@endforeach--}}
                                {{--</dd>--}}
                            {{--</dl>--}}
                        </div>
{{--
                        <div class="col-lg-3">
                        <h4>Total Purchased:<strong>{{ display_currency($purchase_orders->sum('total')) }}</strong><br>
                            Total Balance: <strong>{{ display_currency($purchase_orders->sum('balance')) }}</strong></h4>
                        </div>--}}

                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary waves-effect waves-light mr-1">Filter</button>

                </div>

            </div>

            {{ Form::close() }}

        </div>
    </div>


    <hr>

    <div class="row">
        <div class="col-lg-6">
            @can('po.create')
                <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary waves-effect waves-light mb-2">Create Purchase Order</a>
                {{--<a href="{{ route('purchase-orders.upload') }}" class="btn btn-primary waves-effect waves-light mb-2">Upload Purchase Order</a>--}}
            @endcan
            </div>
        <div class="col-lg-6">
            <div id="datatable-buttons" class="pull-right"></div>
        </div>

    </div>

        <div class="row">
            <div class="col-lg-12">

                <div class="card-box">

                    <p>Showing {{ $purchase_orders->firstItem() }} to {{ $purchase_orders->lastItem() }} of {{ $purchase_orders->total() }}</p>

                    <div class="table-responsive">
                    <table id="po-datatable" class="table table-hover table-striped">
                        <thead>
                        <tr>
                            {{--<th>QB</th>--}}
                            <th>Status</th>
                            <th>Date</th>
                            <th>PO#</th>
                            {{--<th>Manifest#</th>--}}
                            {{--<th>Pkgs</th>--}}
                            <th>Location</th>
                            <th>Vendor</th>
                            {{--<th>License Type</th>--}}
                            {{--<th>Fund</th>--}}
                            <th>Total Units</th>
                            {{--<th>Subtotal</th>--}}
                            {{--<th>Cult. Tax</th>--}}
                            <th>Total</th>
                            <th>Balance</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($purchase_orders as $purchase_order)
                        <tr>
                            {{--<td>--}}
                                {{--<a class="qb_update" href="{{ route('purchase-orders.update', ['id'=>$purchase_order->id]) }}" data-in_qb="{{ $purchase_order->in_qb }}">--}}
                                    {{--<i class="mdi mdi-checkbox-marked text-{{ ($purchase_order->in_qb?"success":"danger") }}"></i>--}}
                                {{--</a>--}}
                            {{--</td>--}}
                            <td><span class="badge badge-{{ status_class($purchase_order->status) }}">{{ ucwords($purchase_order->status) }}</span></td>
                            <td scope="row">{{ $purchase_order->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                            <td class="text-nowrap"><a href="{{ route('purchase-orders.show', $purchase_order) }}">{{ $purchase_order->ref_number }}</a></td>
                            {{--<td class="text-nowrap">--}}
                                {{--@if($purchase_order->manifest_no)--}}
                                {{--<a href="https://ca.metrc.com/reports/transfers/C11-0000347-LIC/manifest?id={{ $purchase_order->manifest_no }}" target="_blank">{{ $purchase_order->manifest_no }} <i class="ion ion-share"></i> </a>--}}
                                {{--@endif--}}
                            {{--</td>--}}

{{--                            <td>{{ $purchase_order->batches->count() }}</td>--}}
                            <td>{!! ($purchase_order->location ? $purchase_order->location->name.($purchase_order->location->trashed()?" <span class='text-danger'>(deleted)</span>":"") : "--")  !!}</td>
                            <td><a href="{{ route('vendors.show', $purchase_order->vendor->id) }}">{{ $purchase_order->vendor->name }}</a></td>
{{--                            <td>{{ $purchase_order->customer_type }}</td>--}}
{{--                            <td>{{ $purchase_order->fund->name }}</td>--}}
                            <td>{{ (!empty($unit_display[$purchase_order->id])?implode(", ", $unit_display[$purchase_order->id]):"") }}</td>
{{--                            <td>{{ display_currency($purchase_order->subtotal) }}</td>--}}
{{--                            <td>{{ display_currency($purchase_order->tax) }}</td>--}}
                            <td>{{ display_currency($purchase_order->total) }}</td>
                            {{--<td>{{ display_currency($purchase_order->balance) }}</td>--}}
                            <td>{{ display_currency($purchase_order->balance) }}</td>
                            <td>
                                {{--<a href="{{ route('purchase-orders.show', ['id'=>$purchase_order->id]) }}" class="m-r-15"><i class="ion-ios7-search-strong"></i></a>--}}
                                {{--<a href="{{ route('purchase-orders.print-qr', ['id'=>$purchase_order->id]) }}" class="m-r-15"><i class="mdi mdi-qrcode"></i></a>--}}
                                <a href="{{ route('purchase-orders.print_po', $purchase_order->id) }}"><i class="ion-printer font-16"></i></a>
                                @permission('accounting')
                                <a href="{{ route('accounting.payables', $purchase_order) }}"><i class=" mdi mdi-chart-areaspline font-16"></i></a>

                                @endpermission
                            </td>
                        </tr>
                        @endforeach

                        </tbody>
                    </table>

                        {{ $purchase_orders->links() }}

                        </div>
                </div>

            </div>

        </div>

@endsection

@section('css')

{{--    <link href="{{ asset('plugins/datatables/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css">--}}

@endsection

@section('js')

{{--    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/dataTables.bootstrap4.min.js') }}"></script>--}}

{{--    <script src="{{ asset('plugins/datatables/dataTables.responsive.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/responsive.bootstrap4.min.js') }}"></script>--}}

{{--    <script src="{{ asset('plugins/datatables/dataTables.buttons.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/buttons.bootstrap4.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/jszip.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/pdfmake.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/vfs_fonts.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/buttons.html5.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/buttons.print.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/buttons.colVis.min.js') }}"></script>--}}

{{--    <script src="{{ asset('plugins/moment/min/moment.min.js') }}"></script>--}}
{{--    <script src="//cdn.datatables.net/plug-ins/1.10.16/sorting/datetime-moment.js"></script>--}}

    <script type="text/javascript">
        $(document).ready(function() {

            // $.fn.dataTable.moment('MM/DD/YYYY');
            //
            // var table = $('#po-datatable').DataTable({
            //     lengthChange: true,
            //     paging: true,
            //     "order": [[ 0, "desc" ]],
            //     "displayLength": 25,
            //     buttons: ['excel'],
            //     columnDefs: [ {
            //         "targets": [7],
            //         "orderable": false
            //     } ]
            // });
            //
            // table.buttons().container().appendTo('#datatable-buttons');

        } );

    </script>


@endsection