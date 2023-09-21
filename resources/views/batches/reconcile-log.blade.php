@php use Illuminate\Support\Facades\Auth; @endphp
@extends('layouts.app')

@section('content')

    <div class="row mb-4">

        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 hidden-print">

            {{ Form::open(['route' => 'batches.reconcile-log', 'method' => 'get']) }}

            <div class="card">

                <div class="card-header cursor-pointer" role="tab" id="filters" >

                    <div class="row">
                        <div class="col-md-3">
                            <a href="#collapse-filters" data-toggle="collapse"><strong><i class="ti-arrow-circle-down"></i> Filters</strong></a>
                            <a href="{{ route('batches.reset-reconcile-log-filters') }}" class="small ml-2">Reset</a>
                        </div>
                        <div class="col-md-9">
                            @if($filters)
                                @foreach($filters as $filter=>$vals)
                                    <span style="margin-right: 15px;">{!! display_filters($filter, $vals) !!}</span>
                                @endforeach
                            @endif
                        </div>
                    </div>

                </div>

                <div id="collapse-filters" class="card-block" role="tabpanel" aria-labelledby="collapse-filters" >

                    <div class="row">

                        <div class="col-lg-4 col-md-5">
                            <dl class="row">
                                <dt class="col-lg-3 text-lg-right">Date Preset:</dt>
                                <dd class="col-lg-9">
                                    @include('_partials._filters_preset_date', ['filters' => $filters])
                                </dd>
                            </dl>

                            <dl class="row">
                                <dt class="col-lg-3 text-lg-right">Batch Name:</dt>
                                <dd class="col-lg-9">
                                    <input class="form-control" type="text" name="filters[name]" placeholder="" value="{{ (isset($filters['name']) ? $filters['name'] : '') }}">
                                </dd>

                                <dt class="col-lg-3 text-lg-right">SKU:</dt>
                                <dd class="col-lg-9">
                                    <input class="form-control" type="text" name="filters[ref_number]" placeholder="" value="{{ (isset($filters['ref_number']) ? $filters['ref_number'] : '') }}">
                                </dd>

                                <dt class="col-lg-3 text-lg-right">Brands:</dt>
                                <dd class="col-lg-9">

                                    <select id="vendor" name="filters[brand_id]" class="form-control">
                                        <option value="">- Select -</option>
                                        @foreach($brands as $brand)
                                            <option value="{{ $brand->id }}"{{ (isset($filters['brand_id']) ? ($brand->id == $filters['brand_id'] ? 'selected' : '' ) : '') }}>{{$brand->name}}</option>
                                        @endforeach
                                    </select>

                                </dd>
                                <dt class="col-lg-3 text-lg-right">Location:</dt>
                                <dd class="col-lg-9">
                                    <select id="location" name="filters[location_id]" class="form-control">
                                        <option value="">- Select -</option>
                                        <option value="0">All</option>
                                        @foreach(Auth::user()->only_my_locations as $location)
                                            <option value="{{ $location->id }}"{{ (isset($filters['location_id']) ? ($location->id == $filters['location_id'] ? 'selected' : '' ) : '') }}>{{$location->name}}</option>
                                        @endforeach
                                    </select>

                                </dd>

                                @can('batches.show.vendor')
                                    <dt class="col-lg-3 text-lg-right">Vendor:</dt>
                                    <dd class="col-lg-9">

                                        <select id="vendor" name="filters[vendor_id]" class="form-control">
                                            <option value="">- Select -</option>
                                            @foreach($vendors as $vendor)
                                                <option value="{{ $vendor->id }}"{{ (isset($filters['vendor_id']) ? ($vendor->id == $filters['vendor_id'] ? 'selected' : '' ) : '') }}>{{$vendor->name}}{{ (!$vendor->active?" (in-active)":"") }}</option>
                                            @endforeach
                                        </select>

                                    </dd>
                                @endcan

                            </dl>

                        </div>

                        <div class="col-lg-8 col-md-7">

                            <div class="row mb-2">
                                <div class="col-12">
                                    <strong>Categories:</strong>
                                </div>
                            </div>
                            <div class="row">

                                @foreach($categories as $category)
                                    <div class="col-xl-3 col-md-4 col-sm-6 col-6">
                                        <div class="checkbox">
                                            <input id="checkbox_cat_{{ $category->id }}" type="checkbox" name="filters[category_id][{{ $category->id }}]" value="{{ $category->name }}" {{ (isset($filters['category_id'])?(in_array($category->id, array_keys($filters['category_id']))?'checked':''):'') }}>
                                            <label for="checkbox_cat_{{ $category->id }}">
                                                {{ $category->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach

                            </div>


                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary waves-effect waves-light mr-1 pull-right">Filter</button>

                </div>
            </div>

            {{ Form::close() }}

        </div>

    </div>

    <div class="row mb-3 hidden-print">
        <div class="col-lg-12">

            <div class="card-box">
                <h4 class="header-title mb-4">Total: <span class="text-{{ ($total_recon_amount < 0 ? "danger": "success") }}"> {{ display_currency($total_recon_amount) }}</span></h4>
                <p>Showing {{ $reconcile_logs->firstItem() }} to {{ $reconcile_logs->lastItem() }} of {{ $reconcile_logs->total() }}</p>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="reconcile-log-datatable">

                        <thead>
                        <tr>
                            <th>Qty Change</th>
                            <th>Location</th>

                            @can('batches.show.vendor')
                            <th>Vendor</th>
                            @endcan

                            <th>Category</th>
                            <th>Brand</th>
                            <th>Original Batch Name</th>
                            <th>Batch Name</th>
                            <th>SKU</th>

                            @can('batches.show.cost')
                            <th>Unit Cost</th>
                            @endcan

                            <th>Gain/Loss</th>
                            <th>Reason</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>By</th>
                        </tr>
                        </thead>

                        <tbody>

                        @foreach($reconcile_logs as $reconcile_log)
                            @if( ! $reconcile_log->batch_converted->exists) @continue; @endif

                            <tr>
                                <td class="text-{{ ($reconcile_log->quantity_transferred>0?"danger":"success") }}"><strong>{{ ($reconcile_log->quantity_transferred<0?"+":"").$reconcile_log->quantity_transferred * -1 }} <small>{{ $reconcile_log->batch_converted->uom }}</small></strong></td>
                                <td>{{ ($reconcile_log->location?$reconcile_log->location->name:"Nest") }}</td>
                                @can('batches.show.vendor')
                                <td>{{ $reconcile_log->batch_converted->purchase_order->vendor->name }}</td>
                                @endcan
                                <td>{{ $reconcile_log->batch_converted->category->name }}</td>
                                <td>{{ $reconcile_log->batch_converted->brand->name??"--" }}</td>
                                <td><a href="{{ route('batches.reconcile-list', $reconcile_log->batch_converted->id) }}">{{ $reconcile_log->original_batch_name }}</a></td>
                                <td><a href="{{ route('batches.reconcile-list', $reconcile_log->batch_converted->id) }}">{{ $reconcile_log->batch_name }}</a></td>

                                <td><a href="{{ route('batches.show', $reconcile_log->batch_converted->id) }}">{{ $reconcile_log->batch_converted->ref_number }}</a></td>

                                @can('batches.show.cost')
                                <td>{{ display_currency($reconcile_log->unit_cost) }}</td>
                                @endcan

                                <td class="text-{{ ($reconcile_log->inventory_loss * -1 < 0?"danger":"success") }}"><strong>{{ display_currency($reconcile_log->inventory_loss * -1) }}</strong></td>
                                <td>{{ $reconcile_log->reason }}</td>
                                <td>{{ $reconcile_log->notes }}</td>
                                <td>{{ $reconcile_log->created_at->format(config('inventorymgmt.date_format')) }}</td>
                                <td>{{ $reconcile_log->user->name }}</td>
                            </tr>

                        @endforeach

                        </tbody>

                    </table>
                </div>

            </div>

        </div>
    </div>

    {{ $reconcile_logs->links() }}

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

{{--    <script type="text/javascript">--}}
{{--        $(document).ready(function() {--}}

{{--            $.fn.dataTable.moment('MM/DD/YYYY');--}}

{{--            // $('[type="date"]').datepicker();--}}

{{--            var table = $('#reconcile-log-datatable').DataTable({--}}
{{--                lengthChange: true,--}}
{{--                paging: true,--}}
{{--                "order": [[ 9, "desc" ]],--}}
{{--                "displayLength": 50,--}}
{{--                buttons: ['excel'],--}}
{{--                columnDefs: [ {--}}
{{--                    "targets": [6],--}}
{{--                    "orderable": false--}}
{{--                } ]--}}
{{--            });--}}

{{--            table.buttons().container().appendTo('#datatable-buttons');--}}
{{--        } );--}}

{{--    </script>--}}

@endsection