@extends('layouts.app')

@section('content')

    <div class="row mb-3 hidden-print">
        <div class="col-lg-12">

            {{ Form::open(['route' => 'batches.intake', 'method' => 'get']) }}

            <div class="card">

                <div class="card-header cursor-pointer" role="tab" id="filters" >

                    <div class="row">
                        <div class="col-md-3">
                            <a href="#collapse-filters" data-toggle="collapse"><strong><i class="ti-arrow-circle-down"></i> Filters</strong></a>
                            <a href="{{ route('batches.intake', ['reset'=>1]) }}" class="small ml-2">Reset</a>
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

                <div id="collapse-filters" class="collapse card-block" role="tabpanel" aria-labelledby="collapse-filters" >

                    <div class="row">

                        <div class="col-lg-4 col-md-5">
                            <dl class="row">
                                <dt class="col-lg-3 text-lg-right">Name:</dt>
                                <dd class="col-lg-9">

                                    <input class="form-control" type="text" name="filters[name]" placeholder="" value="{{ (isset($filters['name']) ? $filters['name'] : '') }}">

                                </dd>
                            </dl>
                            <dl class="row">
                                <dt class="col-lg-3 text-lg-right">SKU:</dt>
                                <dd class="col-lg-9">

                                    <input class="form-control" type="text" name="filters[sku]" placeholder="" value="{{ (isset($filters['sku']) ? $filters['sku'] : '') }}">

                                </dd>
                            </dl>

                            <dl class="row">
                                <dt class="col-lg-3 text-lg-right">UOM:</dt>
                                <dd class="col-lg-9">

                                    <div class="row">
                                        @foreach(config('inventorymgmt.uom') as $uom)
                                            <div class="col-6">
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
                            <div class="row mb-3">
                                <div class="col-12">
                                    <strong>Categories:</strong>
                                </div>
                            </div>
                            <div class="row">

                                @foreach($categories as $category)
                                    <div class="col-xl-3 col-md-4 col-sm-6 col-6">
                                        <div class="checkbox">
                                            <input id="checkbox_{{ $category->id }}" type="checkbox" name="filters[category][{{ $category->id }}]" value="{{ $category->name }}" {{ (isset($filters['category'])?(in_array($category->id, array_keys($filters['category']))?'checked':''):'') }}>
                                            <label for="checkbox_{{ $category->id }}">
                                                {{ $category->name }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach

                            </div>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary waves-effect waves-light mr-1">Filter</button>

                </div>

            </div>

            {{ Form::close() }}

        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <h4 class="text-dark  header-title m-t-0 m-b-30">Inbound Inventory <span id="allocation_count" class="badge badge-info">{{ $intake_batch_locations->count() }}</span></h4>

                <div class="table-responsive">
                    <table id="batch-location-datatable" class="table">

                        <thead>
                        <tr>
                            <th>
{{--                                <div class="checkbox checkbox-primary">--}}
                                <label for="checkbox-bl-all">Select All</label><br>
                                <input id="checkbox-bl-all" type="checkbox" name="" value="all">
{{--                                </div>--}}
                            </th>
                            <th>Origin</th>
                            <th>Location</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Name</th>
                            <th>Qty</th>
                            <th>Cost</th>
                            <th>Price</th>
                            <th>Flex</th>
                            <th>Allocation Date</th>
                            <th></th>
                            <th></th>
                        </tr>
                        </thead>

                        <tbody>

                        @unless($intake_batch_locations->count())
                            <tr>
                                <td colspan="9"><p>No Data</p></td>
                            </tr>
                        @endunless
                        {{--{{ dd($batch_approval) }}--}}
                        @foreach($intake_batch_locations as $batch_location)

                            <tr id="bl_id-{{ $batch_location->id }}">
                                <td>
{{--                                    <div class="checkbox checkbox-primary">--}}
                                        <input id="checkbox-bl-{{ $batch_location->id }}" type="checkbox" name="bl_id-{{ $batch_location->id }}" value="{{ $batch_location->id }}">
{{--                                        <label for="checkbox-bl-{{ $batch_location->id }}">&nbsp;</label>--}}
{{--                                    </div>--}}
                                </td>
                                <td>
                                    @if($batch_location->parent_batch_location)
                                        {{ $batch_location->parent_batch_location->location->name }}
                                    @else
                                        @if($batch_location->quantity < 0)
                                            {{ $batch_location->location->name }}
                                        @else
                                            The Nest
                                        @endif

                                    @endif


                                    {{--                                {{ $batch_location->parent_batch_location?$batch_location->parent_batch_location->location->name:"The Nest" }}--}}
                                </td>
                                <td>
                                    @if($batch_location->child_batch_location)
                                        {{ $batch_location->child_batch_location->location->name }}
                                    @else
                                        @if($batch_location->quantity > 0)
                                            {{ $batch_location->location->name }}
                                        @else
                                            The Nest
                                        @endif
                                    @endif

                                    {{--{{ $batch_location->location->name }}--}}
                                </td>
                                <td>{{ $batch_location->batch->ref_number }}</td>
                                <td>{{ $batch_location->batch->category->name }}</td>
                                <td>{{ ($batch_location->batch->brand?$batch_location->batch->brand->name:"--") }}</td>
                                <td><a href="{{ route('batches.show', $batch_location->batch->id) }}">{{ $batch_location->present()->non_branded_name }}</a></td>
                                <td>{{ $batch_location->quantity }} {{ $batch_location->batch->uom }}</td>
                                <td>{{ display_currency($batch_location->unit_price) }}</td>
                                <td>{{ display_currency($batch_location->suggested_unit_sale_price) }}</td>
                                <td>{{ display_currency($batch_location->min_flex) }}</td>
                                <td>{{ $batch_location->created_at->format(config('inventorymgmt.date_time_format')) }}</td>
                                <td>
                                    {{ Form::open(['class'=>'form-horizontal', 'url'=>route('batch-location.update', $batch_location->id)]) }}
                                    {{ method_field('PUT') }}
                                    {{ Form::hidden('approved', 1) }}
                                    <button class="btn btn-success waves-effect waves-light" type="submit">Intake</button>
                                    {{--<button type="submit" class="btn btn-primary waves-effect waves-light pull-right m-r-10">Open Order</button>--}}
                                    {{ Form::close() }}
                                </td>
                                <td>
                                    {{ Form::open(['class'=>'form-horizontal', 'url'=>route('batch-location.destroy', $batch_location->id)]) }}
                                    {{ method_field('DELETE') }}
                                    <button class="btn btn-danger waves-effect waves-light" type="submit" onclick="return confirm('Are you sure you want to reject these items?')">Reject</button>
                                    {{--<button type="submit" class="btn btn-primary waves-effect waves-light pull-right m-r-10">Open Order</button>--}}
                                    {{ Form::close() }}
                                </td>
                            </tr>

                        @endforeach

                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="5">
                                    <a href="{{ route('batch-location.approve-all-intake') }}" id="intake_all_selected" class="btn btn-success waves-effect waves-light mr-2 submit-all-selected" type="submit" data-action="intake">Intake Selected</a>
                                    <a href="{{ route('batch-location.reject-all-intake') }}" id="reject_all_selected" class="btn btn-danger waves-effect waves-light submit-all-selected" type="submit" data-action="reject">Reject Selected</a>
                                </th>
                                <th colspan="9"></th>
                            </tr>
                        </tfoot>
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

            $.fn.dataTable.moment('MM/DD/YYYY');

            var table = $('#batch-location-datatable').DataTable({
                lengthChange: true,
                paging: false,
                "order": [[ 1, "asc" ]],
                // "displayLength": 100,
                // buttons: ['excel', 'pdf', 'colvis']
                // buttons: ['excel','colvis'],
                columnDefs: [ {
                    "targets": [0, 12, 13],
                    "orderable": false
                }]
            }).buttons().container().appendTo('#datatable-buttons');


            $('#checkbox-bl-all').click(function(e) {
                all_checkbox = this;
                $('#batch-location-datatable tbody input[type="checkbox"]').each(function(idx, elem) {
                    $(elem).prop( "checked", ($(all_checkbox).is(':checked')?true:false) );
                });
            });

            $('.submit-all-selected').click(function(e) {

                $('.alert-success').hide();
                $('.alert-danger').hide();

                selected_checkboxes = $('#batch-location-datatable tbody input[type="checkbox"]:checked');
                action = $(this).data('action');

                e.preventDefault();

                if(!selected_checkboxes.length) {
                    $('.alert-danger').show().find('.error-body').text("Nothing to "+action);
                    return false;
                }

                if(confirm("Are you sure you want to "+action+" "+selected_checkboxes.length+" selected items?")) {

                    fetch($(this).attr('href'), {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        body: JSON.stringify(selected_checkboxes.serializeArray())
                    }).then((response) => response.json())
                        .then((data) => {

                            $(data.ids).each(function(idx, elem) {
                                $('tr#bl_id-'+elem).remove();
                                $('#checkbox-bl-all').prop( "checked", false);
                            });

                            $('#allocation_count').text(data.count);

                            $('.alert-success').show().find('.success-body').text((action=='intake'?"Items accepted!":"Items rejected!"));

                        })
                        .catch((error) => {
                            console.error('Error:', error);

                            $('.alert-danger').show().find('.error-body').text(error);

                        });
                }

                return false;
            });

        });

    </script>

@endsection