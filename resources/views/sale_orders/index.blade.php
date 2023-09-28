@php use Illuminate\Support\Facades\Auth; @endphp
@extends('layouts.app')


@section('content')


    <div class="row">

        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 hidden-print">

            {{ Form::open(['route' => 'sale-orders.index', 'method' => 'get']) }}

            <div class="card">

                <div class="card-header cursor-pointer" role="tab" id="filters" >

                    <div class="row">
                        <div class="col-md-3">
                            <a href="#collapse-filters" data-toggle="collapse"><strong><i class="ti-arrow-circle-down"></i> Filters</strong></a>
                            <a href="{{ route('sale-orders.reset-filters') }}" class="small ml-2">Reset</a>
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

                        <div class="col-lg-3">
                            <div class="form-group">
                                <label for="balance">Order Status:</label>

                                    @foreach(config('inventorymgmt.order_statuses') as $status)

                                        <div class="checkbox checkbox-primary">
                                            <input id="{{ Str::slug($status) }}" name="filters[status][]" type="checkbox" value="{{ $status }}" {{ (isset($filters['status']) && in_array($status, (array)$filters['status']) ? 'checked' : '') }}>
                                            <label for="{{ Str::slug($status) }}">
                                                <span class="badge badge-{{ status_class($status) }}">{{ ucwords($status) }}</span>
                                            </label>
                                        </div>

                                    @endforeach
                                </select>
                            </div>

                        </div>

                        <div class="col-lg-5">

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <select id="date_type" name="filters[date_type]" class="form-control">
                                            <option value="">- Date Type -</option>
                                            <option value="txn_date"{{ (isset($filters['date_type']) ? ($filters['date_type'] == 'txn_date' ? 'selected' : '' ) : '') }}>Ordered At</option>
                                            <option value="delivered_at"{{ (isset($filters['date_type']) ? ($filters['date_type'] == 'delivered_at' ? 'selected' : '' ) : '') }}>Delivered At</option>

                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <select id="date_preset" name="filters[date_preset]" class="form-control">
                                            <option value="">- Date Presets -</option>
                                            @for($i=0; $i<=3; $i++)
                                                <option value="{{ \Carbon\Carbon::now()->firstOfMonth()->subMonth($i)->format('m-Y') }}"{{ (isset($filters['date_preset']) ? (\Carbon\Carbon::now()->firstOfMonth()->subMonth($i)->format('m-Y') == $filters['date_preset'] ? 'selected' : '' ) : '') }}>{{ \Carbon\Carbon::now()->firstOfMonth()->subMonth($i)->format('F, Y') }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="balance">Date Range:</label><br>
                                <div class="row">
                                    <div class="col-6">
                                        <span>From:</span><input class="form-control" type="date" name="filters[from_date]" value="{{ (isset($filters['from_date']) ? $filters['from_date'] : '') }}">
                                    </div>
                                    <div class="col-6">
                                        <span>To:</span><input class="form-control" type="date" name="filters[to_date]" value="{{ (isset($filters['to_date']) ? $filters['to_date'] : '') }}">
                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="col-lg-4">
                            <div class="row">
                                <div class="col-lg-6 form-group">

                                    <input id="filter_customer" type="text" list="filter_customer_list" class="form-control" autocomplete="off" value="{{ ( ! empty($filter_customer) ? $filter_customer->name : '') }}" placeholder="-- Customer (Bill To) --">

                                    <input type="hidden" id="filter_customer_id" name="filters[customer]" value="">

                                    <datalist id="filter_customer_list">
                                        @foreach($customers as $customer )
                                            <option value="{{ $customer->name }}" id="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </datalist>

                                </div>

                                @level(60)
                                    <div class="col-lg-6 form-group">
                                        <select id="sales_rep" name="filters[sales_rep]" class="form-control">
                                            <option value="">- Sales Rep -</option>
                                            <option value="None"{{ (isset($filters['sales_rep']) ? ("None" == $filters['sales_rep'] ? 'selected' : '' ) : '') }}>None</option>
                                            @foreach($sales_reps as $sales_rep)
                                                <option value="{{ $sales_rep->id }}"{{ (isset($filters['sales_rep']) ? ($sales_rep->id == $filters['sales_rep'] ? 'selected' : '' ) : '') }}>{{$sales_rep->name}}{{ ( $sales_rep->active ? "" : " (Inactive)") }}</option>
                                            @endforeach
                                        </select>

                                    </div>
                                @endlevel

                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        {{--<label for="balance">Order Balance:</label>--}}
                                        <select id="balance" name="filters[balance]" class="form-control">
                                            <option value="">- Order Balance -</option>
                                            <option value="yes"{{ (isset($filters['balance']) ? ($filters['balance'] == 'yes' ? 'selected' : '' ) : '') }}>Yes</option>
                                            <option value="no"{{ (isset($filters['balance']) ? ($filters['balance'] == 'no' ? 'selected' : '' ) : '') }}>No</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <input class="form-control" type="text" placeholder="SO#" name="filters[ref_number]" value="{{ (isset($filters['ref_number']) ? $filters['ref_number'] : '') }}">
                                    </div>
                                </div>

                            </div>


                            <div class="form-group">
                                <input class="form-control" type="text" placeholder="Notes" name="filters[notes]" value="{{ (isset($filters['notes']) ? $filters['notes'] : '') }}">

                            </div>


                        </div>

                    </div>
                    {{--<hr>--}}
                    <button type="submit" class="btn btn-primary waves-effect waves-light mr-1 pull-right">Filter</button>

                </div>

            </div>

            {{ Form::close() }}

        </div>


    </div>


    <div class="row hidden-print mb-4">
    </div>


    <div class="row">

        <div class="col-lg-12">

            <div class="card-box">

                <div class="row">
                    <div class="col-lg-12">
                        <p class="clearfix">
                            <a href="{{ route('sale-orders.export') }}" class="pull-right btn btn-secondary pull-left ">Export Sale Orders</a>
                            <a href="javascript:void(0)" class="pull-right btn btn-primary " data-toggle="modal" data-target=".create-sale-order">Create Sale Order</a>
                        </p>
                    </div>
                </div>

                <div class="modal fade create-sale-order" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" style="display: none;" aria-hidden="true">
                    <div class="modal-dialog modal-md">
                        <div class="modal-content">

                            {{ Form::open(['url'=>route('sale-orders.store'), 'method'=>'post']) }}
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                                <h4 class="modal-title" id="mySmallModalLabel">Create Sale Order</h4>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">

                                        <div class="form-group">
                                            <label class="control-label">Order Date <span class="text-danger">*</span></label>
                                            <input class="form-control" type="date" name="txn_date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" required>
                                        </div>

                                        <div class="form-group">
                                            <label for="destination_user_id" class="control-label">Customer Name <span class="text-danger">*</span></label>
                                            <a href="{{ route('users.create') }}?role=Customer" class="control-label pull-right">Create +</a>

                                            <input id="destination_user" required autocomplete="off" name="_new_customer" type="text" list="destination_user_id_list" class="form-control" value="" placeholder="-- Customer --">

                                            <input type="hidden" id="destination_user_id" name="destination_user_id" value="">

                                            <datalist id="destination_user_id_list">
                                                @foreach($customers as $customer )
                                                    <option value="{{ $customer->name }}" id="{{ $customer->id }}">{{ $customer->name }}</option>
                                                @endforeach
                                            </datalist>
                                        </div>

                                        @if(Auth::user()->active_locations->count() > 1)
                                            <div class="form-group">
                                                <label class="control-label">Location <span class="text-danger">*</span></label>
                                                <select class="form-control mb-2" name="location_id" required="required">
                                                    <option value="">-- Location --</option>
                                                    @foreach(Auth::user()->active_locations as $my_location)
                                                        <option value="{{ $my_location->id }}" >{{ $my_location->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif

                                        @level(60)

                                        <div class="form-group">

                                            <label class="control-label">Sales Rep <span class="text-danger">*</span></label>
                                            <select class="form-control mb-2" name="sales_rep_id" required="required">
                                                <option value="">-- Sales Rep --</option>
                                                @foreach($sales_reps as $sales_rep)
                                                    <option value="{{ $sales_rep->id }}" @if ($sales_rep->id == 14) selected="selected" @endif>{{ $sales_rep->name }}</option>
                                                @endforeach
                                            </select>

                                        </div>

                                        @endlevel

                                    </div>

                                </div>

                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Create</button>
                            </div>

                            {{ Form::close() }}

                        </div><!-- /.modal-content -->
                    </div><!-- /.modal-dialog -->
                </div>

                <p>Showing {{ $sale_orders->firstItem() }} to {{ $sale_orders->lastItem() }} of {{ $sale_orders->total() }}</p>

                <div class="table-responsive">
                <table id="sale-order-table" class="table table-striped">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Date</th>
                        <th>SO#</th>
                        <th>Status</th>
                        <th>Location</th>
                        <th>Customer</th>
                        <th>Sales Rep</th>
                        <th>Units</th>
                        <th>Total</th>
                        <th>Balance</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($sale_orders as $sale_order)
                        <tr class="{{ ($sale_order->requires_manager_approval?"table-warning":"") }}">
                            <td>
                                @if($sale_order->requires_manager_approval)
                                    <i class="mdi mdi-alert text-warning"></i>
                                @endif
                            </td>
                            <td scope="row">{{ $sale_order->txn_date->format(config('inventorymgmt.date_format')) }}</td>
                            <td class="text-nowrap"><a href="{{ route('sale-orders.show', $sale_order) }}">{{ $sale_order->ref_number }}</a></td>
                            <td><span class="badge badge-{{ status_class($sale_order->status) }}">{{ ucwords($sale_order->status) }}</span>
                            </td>
                            <td>{{ ucwords($sale_order->location->name) }}{!! ($sale_order->location->trashed()?" <span class='text-danger'>(deleted)</span>":"") !!}</td>
                            <td><a href="{{ route('users.show', $sale_order->customer->id) }}">{{ $sale_order->customer->name  }}</a>
                            </td>
                            <td>{{ ($sale_order->sales_rep?$sale_order->sales_rep->name:'--') }}</td>
                            <td>{{ (!empty($units_purchased[$sale_order->id]) ? implode(", ", $units_purchased[$sale_order->id]) : '--') }}</td>
                            <td>{{ display_currency($sale_order->total) }}</td>
                            <td>{{ display_currency($sale_order->balance) }}</td>
                        </tr>
                    @endforeach

                    </tbody>

                </table>
                {{ $sale_orders->links() }}

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

    {{--<script src="{{ asset('plugins/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>--}}
    {{--<script src="{{ asset('plugins/bootstrap-daterangepicker/daterangepicker.js') }}"></script>--}}
    {{--<script src="../plugins/bootstrap-daterangepicker/daterangepicker.js"></script>--}}

    <script type="text/javascript">
        $(document).ready(function() {

            $('#filter_customer').change(function () {

                var el=$("#filter_customer")[0];  //used [0] is to get HTML DOM not jquery Object
                var dl=$("#filter_customer_list")[0];

                if(el.value.trim() != '') {
                    var opSelected = dl.querySelector(`[value="${el.value}"]`);
                    $('#filter_customer_id').val(opSelected.getAttribute('id'));
                }

            });

            $('#destination_user').change(function () {

                var el=$("#destination_user")[0];  //used [0] is to get HTML DOM not jquery Object
                var dl=$("#destination_user_id_list")[0];

                if(el.value.trim() != '') {
                    var opSelected = dl.querySelector(`[value="${el.value}"]`);

                    $('#destination_user_id').val(opSelected.getAttribute('id'));
                }

            });


        } );


    </script>


@endsection