@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <div class="row">

                <div class="col-lg-12 mb-3">
                <div id="datatable-buttons" class="pull-right"></div>
                </div>

                </div>

                <div class="table-responsive">

                    <table id="batches-datatable" class="table table-hover">

                        <thead>
                        <tr>
                            <th>PO Date</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Name</th>

                            @level(60)
                                <th>Purchased</th>
                                <th>Inventory</th>
                            @else
                                <th>Inventory</th>
                            @endlevel

                            @can('batches.show.vendor')
                                    <th>Vendor</th>
                            @endcan

                            @level(60)
                                <th>Unit Cost</th>
                            @endlevel

                            <th>Sale Price</th>
                            <th>Added</th>
                        </tr>
                        </thead>

                        <tbody>
                        @foreach($batches as $batch)

                            <tr>
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
                                <td>
                                    <a href="{{ route('batches.show', $batch->id) }}">
                                        {{ $batch->present()->branded_name }}
                                    </a>
                                </td>

                                @level(60)
                                    <td>{!! display_inventory($batch, 'units_purchased') !!}</td>
                                    <td>{!! display_inventory($batch, 'available_inventory') !!}</td>
                                @else
                                    <td>{!! display_inventory($batch, 'available_inventory') !!}</td>
                                @endlevel

                                @can('batches.show.vendor')
                                    <td>
                                        @if($batch->purchase_order)
                                            {{ $batch->purchase_order->vendor->name }}
                                        @endif
                                    </td>
                                @endcan

                                @level(60)
                                    <td>{{ display_currency($batch->unit_price) }}</td>
                                @endlevel

                                <td>{{ display_currency($batch->suggested_unit_sale_price) }}</td>
                                <td data-sort="{{ strtotime($batch->added_to_inventory_date) }}">{{ $batch->added_to_inventory }}</td>
                            </tr>

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

            $.fn.dataTable.moment('MM/DD/YYYY');

            var table = $('#batches-datatable').DataTable({
                lengthChange: true,
                paging: true,
                "order": [[ 1, "asc" ]],
                "displayLength": 100,
                buttons: ['excel'],
                columnDefs: [ {
                    // "targets": [$('#batches-datatable thead tr').children('th').length - 1],
                    "orderable": false
                }]
            });

            table.buttons().container().appendTo('#datatable-buttons');
        } );

    </script>

@endsection