@extends('layouts.app')


@section('content')

    <div class="row">
        <div class="col-lg-12">
                <a href="{{ route('categories.create') }}" class="btn btn-primary waves-effect waves-light mb-2 pull-right">Create Category</a>
        </div>

    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <div class="table-responsive">
                    <table id="categories-datatable" class="table table-hover">

                        <thead>
                            <tr>
                                <th style="width: 45px">Active</th>
                                <th>Name</th>
                                <th>Min Sale Price</th>
                                <th>Max Sale Price</th>
                                <th>Avg Sale Price</th>
                                <th>Qty</th>
                                <th>Price Ranges</th>
                                <th style="width: 10%; white-space: nowrap"></th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($categories->groupBy('name') as $category_name=>$grouped_category)
                                <tr>
                                    <td class="text-center">
                                        @if($grouped_category->first()->is_active)
                                            <i class="mdi mdi-check text-success font-16"></i>
                                        @else
                                            <i class=" mdi mdi-window-close text-danger font-16"></i>
                                        @endif
                                    </td>
                                    <td>{{ $category_name }}</td>
                                    <td>
                                        @foreach($grouped_category as $category)
                                            <small>{{ $category->batch_uom }}:</small> {{ display_currency($category->batch_min_price) }}<br />
                                        @endforeach
                                    </td>

                                    <td>
                                        @foreach($grouped_category as $category)
                                            <small>{{ $category->batch_uom }}:</small> {{ display_currency($category->batch_max_price) }}<br />
                                        @endforeach
                                    </td>

                                    <td>
                                        @foreach($grouped_category as $category)
                                            @if($grouped_category->count()>1)
                                                {{ $category->batch_uom }}:
                                            @endif
                                            {{ display_currency($category->batch_avg_price) }}<br />

                                        @endforeach
                                    </td>

                                    <td>
                                        @foreach($grouped_category as $category)
                                            {{ number_format($category->batch_inventory) }} {{ $category->batch_uom }}<br />
                                        @endforeach

                                    </td>
                                    <td>
                                        <a href="{{ route('categories.category-price-ranges.index', $grouped_category->first()) }}" class="btn btn-secondary btn mr-2">
                                            @if($grouped_category->first()->price_ranges->count())
                                                Edit
                                                <span class="badge badge-primary ml-1">{{ $grouped_category->first()->price_ranges->count() }}</span>
                                            @else
                                                Add
                                            @endif
                                        </a>
                                    </td>
                                    <td style="text-align: right;">
                                        <div class="d-flex justify-content-end">

                                        <a href="{{ route('categories.edit', $grouped_category->first()) }}" class="btn btn-secondary btn mr-2"><i class="ion-edit"></i></a>

                                        <form action="{{ route('categories.destroy', $grouped_category->first()->id) }}" method="POST" class="d-inline">

                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}

                                            <button type="submit" class="btn btn-danger" {{ ( $grouped_category->first()->batches->count()?"disabled='disabled'":"") }}><i class="ion-trash-a"></i></button>

                                        </form>
                                        </div>
                                    </td>

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
{{--    <script src="{{ asset('plugins/datatables/jszip.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/pdfmake.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/vfs_fonts.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/buttons.html5.min.js') }}"></script>--}}
{{--    <script src="{{ asset('plugins/datatables/buttons.print.min.js') }}"></script>--}}
    <script src="{{ asset('plugins/datatables/buttons.colVis.min.js') }}"></script>

    <script src="{{ asset('plugins/moment/min/moment.min.js') }}"></script>
    <script src="//cdn.datatables.net/plug-ins/1.10.16/sorting/datetime-moment.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {

            $.fn.dataTable.moment('MM/DD/YYYY');

            var table = $('#categories-datatable').DataTable({
                lengthChange: true,
                paging: true,
                "order": [[ 1, "asc" ]],
                "displayLength": 100,
                "autoWidth": true,
                "columnDefs": [
                    // { "orderable": false, "targets": 0 },
                    // { "orderable": false, "targets": 7 },
                    // { "orderable": false, "targets": 8 }

                ],
                buttons: ['excel', 'pdf', 'colvis']
            });

            table.buttons().container().appendTo('#datatable-buttons');

        } );

    </script>


@endsection