@php use Illuminate\Support\Facades\Auth; @endphp

@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-12">



        </div>
    </div>


    <div class="row">
        <div class="col-lg-12">
            @if(Auth::user()->can('batches.create'))
                <a href="{{ route('batches.create') }}" class="pull-left btn btn-primary mb-2">Create Batch</a>
            @endif

                <a href="javascript:void(0)" class="btn btn-primary pull-right" data-toggle="modal" data-target="#sale_order_filters">Filters {{ (count($filters)?"(".count($filters).")":"") }} <i class="mdi mdi-filter"></i></a>

                <div id="sale_order_filters" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true"  style="display: none;">
                    <div class="modal-dialog modal-lg" style="max-width: 80% !important;">
                        <div class="modal-content">

                            {{ Form::open(['route' => 'batches.index', 'method' => 'get']) }}

                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                <h4 class="modal-title" id="myModalLabel">Inventory Filters</h4>
                            </div>
                            <div class="modal-body">
                                <div class="row">


                                    <div class="col-lg-4 col-md-5">
                                        <dl class="row">
                                            <dt class="col-lg-3 text-lg-right">Name:</dt>
                                            <dd class="col-lg-9">

                                                <input class="form-control" type="text" name="filters[name]" placeholder="" value="{{ (isset($filters['name']) ? $filters['name'] : '') }}">

                                            </dd>

                                            <dt class="col-lg-3 text-lg-right">SKU:</dt>
                                            <dd class="col-lg-9">
                                                <input class="form-control" type="text" name="filters[batch_id]" placeholder="" value="{{ (isset($filters['batch_id']) ? $filters['batch_id'] : '') }}">
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

                                        </dl>

                                        @can('batches.show.vendor')
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
                                        @endcan


                                        <dl class="row">
                                            <dt class="col-lg-3 text-lg-right">Inventory:</dt>
                                            <dd class="col-lg-9">
                                                @level(60)
                                                <div class="checkbox">
                                                    <input id="checkbox_not_available_inventory" type="checkbox" name="filters[not_available_inventory]" value="1" {{ (isset($filters['not_available_inventory']) ? 'checked' : '') }}>
                                                    <label for="checkbox_not_available_inventory">
                                                        Only show not available inventory
                                                    </label>
                                                </div>
                                                <div class="checkbox">
                                                    <input id="checkbox_available_inventory" type="checkbox" name="filters[available_inventory]" value="1" {{ (isset($filters['available_inventory']) ? 'checked' : '') }}>
                                                    <label for="checkbox_available_inventory">
                                                        Only show available inventory
                                                    </label>
                                                </div>
                                                <div class="checkbox">
                                                    <input id="checkbox_pending_inventory" type="checkbox" name="filters[pending_inventory]" value="1" {{ (isset($filters['pending_inventory']) ? 'checked' : '') }}>
                                                    <label for="checkbox_pending_inventory">
                                                        Only show pending inventory
                                                    </label>
                                                </div>
                                                @endlevel


                                                <div class="checkbox">
                                                    <input id="checkbox_non_inventory" type="checkbox" name="filters[non_inventory]" value="1" {{ (isset($filters['non_inventory']) ? 'checked' : '') }}>
                                                    <label for="checkbox_non_inventory">
                                                        Only show non-inventory items
                                                    </label>
                                                </div>
                                            </dd>
                                        </dl>


                                        <dl class="row">
                                            <dt class="col-lg-3 text-lg-right">Type:</dt>
                                            <dd class="col-lg-9">

                                                <div class="row">
                                                    @foreach(config('inventorymgmt.product_type') as $strain_type)
                                                        <div class="col-4">
                                                            <div class="checkbox">
                                                                <input id="checkbox_{{ $strain_type }}" type="checkbox" name="filters[type][{{ $strain_type }}]" value="{{ $strain_type }}" {{ (isset($filters['type'])?(in_array($strain_type, array_keys($filters['type']))?'checked':''):'') }}>
                                                                <label for="checkbox_{{ $strain_type }}">
                                                                    {{ $strain_type }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </dd>
                                        </dl>

                                        <dl class="row">
                                            <dt class="col-lg-3 text-lg-right">UOM:</dt>
                                            <dd class="col-lg-9">

                                                <div class="row">
                                                    @foreach(config('inventorymgmt.uom') as $uom)
                                                        <div class="col-xl-3 col-md-6 col-xs-4">
                                                            <div class="checkbox">
                                                                <input id="checkbox_{{ $uom }}" type="checkbox" name="filters[uom][{{ $uom }}]" value="{{ $uom }}" {{ (isset($filters['uom'])?(in_array($uom, array_keys($filters['uom']))?'checked':''):'') }}>
                                                                <label for="checkbox_{{ $uom }}">
                                                                    {{ $uom }}
                                                                </label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </dd>
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
                                                        <input id="checkbox_cat_{{ $category->id }}" type="checkbox" name="filters[category][{{ $category->id }}]" value="{{ $category->name }}" {{ (isset($filters['category'])?(in_array($category->id, array_keys($filters['category']))?'checked':''):'') }}>
                                                        <label for="checkbox_cat_{{ $category->id }}">
                                                            {{ $category->name }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach

                                        </div>

                                    </div>
                                </div>

                            </div>
                            <div class="modal-footer">
                                <a href="{{ route('batches.reset-filters') }}" class="btn btn-secondary">Reset</a>
                                <button type="submit" class="btn btn-primary waves-effect waves-light mr-1 pull-right">Filter</button>
                            </div>
                            {{ Form::close() }}
                        </div>
                    </div><!-- /.modal-dialog -->
                </div>

        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">


            <div class="card-box clearfix">

                @if($batches->count())
                    @if($batches->groupBy('location_name')->count() > 1)
                    @level(60)
                    <h4 class="header-title m-t-0 m-b-30">Total Value: {{ display_currency($batches->sum('location_value')) }}</h4>
                    @endlevel
                    @endif

                    <ul class="nav nav-tabs">

                        @foreach($batches->groupBy('location_name') as $location_name=>$batch_items)

                            <li class="nav-item">
                                <a href="#location_{{ Str::slug($location_name) }}" data-toggle="tab" aria-expanded="false" class="nav-link{{ (!$loop->index?" active":"") }}">
                                    <h4>
                                        @if(Auth::user()->only_my_locations->count()>1)
                                        {{ $location_name }}:
                                        @endif
                                        @can('batches.show.cost'){{ display_currency($batch_items->sum('location_value')) }}@endcan <small>({{ $batch_items->count() }})</small>
                                    </h4>
                                </a>
                            </li>

                        @endforeach

                    </ul>
                    <div class="tab-content">

                        @foreach($batches->groupBy('location_name') as $location_name=>$batch_items)

                            <div class="tab-pane fade{{ (!$loop->index?" active show":"") }}" id="location_{{ Str::slug($location_name) }}" aria-expanded="false">

                                <div class="row">
                                    <div class="col-lg-12 mb-3">
                                        <div id="{{ $location_name }}-batches-excel" class="pull-right datatable-buttons"></div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table id="{{ $location_name."-batches-table" }}" class="table table-hover batches-datatable">

                                        <thead>
                                        <tr>
                                            <th>PO Date</th>
                                            <th>SKU</th>
                                            <th>Category</th>
                                            <th>Batch Name</th>
                                            <th style="white-space: nowrap">Location Batch Name</th>

                                            @level(60)
                                                <th>Purchased</th>
                                                <th style="white-space: nowrap">Available <i class="ion-help-circled" data-toggle="tooltip" data-placement="top" title="" data-original-title="Quantity available for sale"></i></th>
                                                <th style="white-space: nowrap">Pending <i class="ion-help-circled" data-toggle="tooltip" data-placement="top" title="" data-original-title="Quantity on hold and ready-to-pack that have not been fulfilled"></i></th>
                                                <th>Total <span style="white-space: nowrap">Inventory <i class="ion-help-circled" data-toggle="tooltip" data-placement="top" title="" data-original-title="Total inventory on-hand. Available + Pending"></i></span></th>
                                            @else
                                                <th>Inventory</th>
                                            @endlevel

                                            @can('batches.show.vendor')
                                                <th>Vendor</th>
                                            @endcan

                                            @can('batches.show.cost')
                                                <th>Unit Cost</th>
                                            @endcan

                                            <th>Sale Price</th>
                                            <th>Added</th>

                                        </tr>
                                        </thead>

                                        <tbody>

                                        @foreach($batch_items as $batch)

                                            <tr>
                                                {{--<td>{{ $batch->id }}</td>--}}
                                                <td>
                                                    @if($batch->purchase_order)
                                                        {{ $batch->purchase_order->txn_date->format(config('inventorymgmt.date_format')) }}
                                                    @endif
                                                </td>
                                                <td class="hidden-print">
                                                    <a href="{{ route('batches.show', $batch->id) }}">{{ $batch->ref_number }}</a>
                                                </td>
                                                <td>
                                                    {{ $batch->category->name }}
                                                </td>

                                                <td style="min-width: 200px">
{{--                                                    @if($batch->original_batch_name != $batch->present()->branded_name)--}}
                                                        {{ $batch->original_batch_name }}
{{--                                                    @endif--}}
                                                </td>

                                                <td style="min-width: 150px">
                                                    <a href="{{ route('batches.show', $batch->id) }}">
                                                        {{ $batch->present()->branded_name }}
                                                    </a>
                                                </td>

                                                @level(60)
                                                    <td data-sort="{{ $batch->units_purchased }}">
                                                        @if($batch->track_inventory)
                                                            {!! display_inventory($batch, 'units_purchased') !!}
                                                        @else
                                                            --
                                                        @endif
                                                    </td>

                                                    <td data-sort="{{ $batch->available_inventory }}" style="white-space: nowrap">
                                                        @if($batch->track_inventory)
                                                            {!! display_inventory($batch, 'available_inventory') !!}
{{--                                                            @if($batch->canAllocate())--}}
{{--                                                                <a href="{{ route('batches.allocate', $batch->id) }}" class=""><i class=" mdi mdi-logout"></i></a>--}}
{{--                                                            @endif--}}
                                                        @else
                                                            --
                                                        @endif
                                                    </td>

                                                    <td data-sort="{{ $batch->pending_inventory }}">
                                                        @if($batch->track_inventory)
                                                            {!! display_inventory($batch, 'pending_inventory') !!}
                                                        @else
                                                            <i>--</i>
                                                        @endif
                                                    </td>

                                                    <td data-sort="{{ $batch->onhand_inventory }}">
                                                        @if($batch->track_inventory)
                                                            {!! display_inventory($batch, 'onhand_inventory') !!}
                                                        @else
                                                            <i>Unlimited</i>
                                                        @endif
                                                    </td>

                                                @else
                                                    <td data-sort="{{ $batch->available_inventory }}">
                                                        @if($batch->track_inventory)
                                                            {!! display_inventory($batch, 'available_inventory') !!}
                                                        @else
                                                            <i>Unlimited</i>
                                                        @endif
                                                    </td>
                                                @endlevel

                                                    @can('batches.show.vendor')
                                                        <td>
                                                            {{ $batch->purchase_order->vendor->name??"--" }}
                                                        </td>
                                                    @endcan

                                                    @can('batches.show.cost')
                                                        <td>{{ display_currency($batch->unit_price) }}</td>
                                                    @endcan

                                                    <td>{{ display_currency($batch->suggested_unit_sale_price) }}</td>

                                                    <td data-sort="{{ strtotime($batch->allocation_created_at->format('m/d/Y H:i:s')) }}">{{ $batch->allocation_created_at->diffForHumans() }}</td>

                                            </tr>

                                        @endforeach

                                        </tbody>
                                    </table>
                                </div>

                            </div>

                        @endforeach
{{-->>>>>>> staging--}}

                    </div>

                @else
                    <h4>No Inventory</h4>
                @endif
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

            $.fn.dataTable.moment('MM/DD/YYYY');

            // $('[type="date"]').datepicker();
            $('.batches-datatable').each(function(idx, tbl) {
                // console.log($(this));

                var table = $(this).DataTable({
                    lengthChange: true,
                    paging: true,
                    "order": [[ 1, "asc" ]],
                    "displayLength": 100,
                    // buttons: ['excel', 'pdf', 'colvis']
                    buttons: ['excel'],
                    columnDefs: [ {
                        // "targets": [$(this).find('thead tr th').length - 1],
                        // "orderable": false
                    }]
                }).buttons().container().appendTo('.datatable-buttons');
            });


        } );

    </script>

@endsection