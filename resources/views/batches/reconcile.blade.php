@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12 mb-3">
            <a class="btn btn-primary" href="{{ route('batches.reconcile-log') }}">All Logs</a>

            @if($target_batch_id)
            <a class="btn btn-primary" href="{{ route('batches.reconcile-log', ['batch'=>$target_batch_id, 'filters'=>['date_preset'=>'all']]) }}">Batch Logs</a>
            @endif

        </div>
    </div>

    <div class="row mb-3 hidden-print">
        <div class="col-lg-12">

            {{ Form::open(['route' => 'batches.reconcile', 'method' => 'post']) }}

            {{ Form::hidden('batch_id', $target_batch_id) }}

            <div class="card-box">

                <div class="row">
                    <div class="col-lg-12 mb-3">
                        <div id="datatable-buttons" class="pull-right"></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="inventory-datatable">

                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Parent Name</th>
                                <th style="min-width: 150px">Name</th>

                                <th>Available</th>
                                <th>Pending</th>
                                <th style="min-width: 100px; white-space: nowrap">Total Inventory <i class="ion-help-circled" data-toggle="tooltip" data-placement="top" title="" data-original-title="This is the total inventory currently on-hand. This value can not be less than the pending inventory."></i> </th>
                                <th>Change</th>
                                <th style="min-width: 100px">Reason</th>
                                <th style="min-width: 150px">Notes</th>

                                <th>Cost</th>
                                <th>Vendor</th>

                                <th>Added</th>
                            </tr>
                        </thead>

                        <tbody>

                        @foreach($batches as $batch)
{{--                            {{ dd($batch) }}--}}
                            <tr>
                                <td>
                                    {{ $batch->location_name }}
                                    <input type="hidden" name="batch[{{ $batch->id }}][{{$batch->location_id}}][location_name]" value="{{ $batch->location_name }}" />
                                </td>
                                <td><a href="{{ route('batches.show', $batch->id) }}">{{ $batch->ref_number }}</a></td>
                                <td>{{ $batch->category->name }}</td>
                                <td>{{ $batch->original_batch_name }}</td>
                                <td>{{ $batch->present()->branded_name }}</td>

                                <td data-sort="{{ $batch->available_inventory }}">{{ (float)$batch->available_inventory }} {{ $batch->uom }}</td>
                                <td data-sort="{{ $batch->pending_inventory }}">{{ (float)$batch->pending_inventory }} {{ $batch->uom }}</td>
                                <td data-sort="{{ $batch->onhand_inventory }}">
                                    <div class="input-group bootstrap-touchspin">
                                        <input type="text" name="batch[{{$batch->id}}][{{$batch->location_id}}][new_value]" value="{{ $batch->onhand_inventory }}" data-onhand_inventory="{{ (float)$batch->onhand_inventory }}" data-pending_inventory="{{ (float)$batch->pending_inventory }}" class="form-control quantity_to_reconcile">
                                        <span class="input-group-addon bootstrap-touchspin-postfix">{{ $batch->uom }}</span>
                                    </div>
                                    <span class="original_inventory text-muted" style="display: none"><small>Current Inventory: {{ $batch->onhand_inventory }} {{ $batch->uom }}</small></span>
                                    <input type="hidden" name="batch[{{ $batch->id }}][{{$batch->location_id}}][current_value]" value="{{ $batch->onhand_inventory }}" />
                                </td>
                                <td><span class="quantity_change"><strong></strong></span></td>
                                <td>
                                    <div class="input-group">
                                        <select name="batch[{{$batch->id}}][{{$batch->location_id}}][reason]" class="form-control reconcile_reason" size="1">
                                            <option value="">-- Select --</option>
                                            <option value="Shrinkage">Shrinkage</option>
                                            <option value="Defective">Defective</option>
                                            <option value="Samples">Samples</option>
                                            <option value="Media/Marketing">Media/Marketing</option>
                                            <option value="Promotional">Promotional</option>
                                            <option value="Waste">Waste</option>
                                            <option value="Overage">Overage</option>
                                            <option value="Found">Found</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <textarea name="batch[{{$batch->id}}][{{$batch->location_id}}][notes]" class="form-control reason_note"></textarea>
                                    </div>
                                </td>


                                <td>{{ display_currency($batch->unit_price) }}</td>
                                <td>{{ ($batch->purchase_order?$batch->purchase_order->vendor->name:"--") }}</td>


                                <td data-sort="{{ strtotime($batch->added_to_inventory_date) }}">{{ $batch->added_to_inventory }}</td>

                            </tr>

                        @endforeach

                        </tbody>

                    </table>
                </div>
                {{--@endforeach--}}

            </div>

            <button class="btn btn-primary waves-effect waves-light" type="submit">Reconcile</button>

            {{ Form::close() }}
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

            $('.quantity_to_reconcile').change(function() {

                var container_row = $(this).parents('tr');

                var qty_change = parseFloat($(this).val()) - parseFloat($(this).data('onhand_inventory'));

                var change_html = $(container_row).find('span.quantity_change strong');

                // console.log(qty_change);
                // console.log($(this).val());
                // console.log($(this).data('pending_inventory'));

                if(qty_change != 0 && $(this).val() >= $(this).data('pending_inventory')) {

                    change_html.text((qty_change > 0 ? "+" : "") + qty_change + " " + $(this).next().text());

                    change_html.removeClass().addClass("text-" + (qty_change > 0 ? "success" : "danger"));

                    $(container_row).find('.original_inventory').show();

                    // console.log($(container_row).find('span.quantity_change').parent('td'));
                    $(container_row).find('span.quantity_change').parent('td').attr('data-sort', qty_change);
                    // table.order([7, 'desc']).draw();


                    $(container_row).find('.reconcile_reason').prop('required', true);
                    $(container_row).find('.reason_note').prop('required', true);
                } else {
                    change_html.text("").removeClass();
                    $(container_row).find('.original_inventory').hide();

                    $(this).val($(this).data('onhand_inventory'));

                    $(container_row).find('.reconcile_reason').prop('required', false);
                    $(container_row).find('.reason_note').prop('required', false);
                }

            });


            $.fn.dataTable.moment('MM/DD/YYYY');

            var table = $('#inventory-datatable').DataTable({
                // lengthChange: true,
                lengthChange: true,
                paging: true,
                "order": [[ 0, "asc" ]],
                "displayLength": 25,
                buttons: ['excel'],
                columnDefs: [
                    { "targets": [5,9,10], "orderable": false }
                ]
            });

            table.buttons().container().appendTo('#datatable-buttons');
        } );

    </script>

@endsection