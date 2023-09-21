@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12 mb-3">
            <a class="btn btn-primary" href="{{ $back_link }}">Back</a>
        </div>
    </div>

    {{--{{ $reconcile_logs->links() }}--}}

    <div class="row mb-3 hidden-print">
        <div class="col-lg-12">

            <h3>{!! $heading3 !!}</h3>

            <div class="card-box">

                <div class="row">
                    <div class="col-lg-12 mb-3">
                        <div id="datatable-buttons" class="pull-right"></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped" id="sale-order-activity-log-datatable">

                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Activity</th>
                            <th>Properties</th>
                            <th>By</th>
                        </tr>
                        </thead>

                        <tbody>

                        @foreach($model->activity_logs as $activity_log)

                            {{--@if( ! $reconcile_log->batch_converted->exists) @continue; @endif--}}
                            <tr>
                                <td>{{ $activity_log->id }}</td>
                                <td>{{ $activity_log->created_at->format(config('inventorymgmt.date_time_format')) }}</td>
                                <td><span class="badge badge-{{ status_class(Str::lower($activity_log->description)) }}">{{ $activity_log->description }}</span></td>
                                <td>
                                    @foreach($activity_log->properties as $name=>$val)
                                        <strong>{{ (!is_int($name)?$name.":":"") }}</strong> {{ $val }}<br>
                                    @endforeach
                                </td>

                                <td>{{ $activity_log->causer?$activity_log->causer->name:"System" }}</td>
                            </tr>

                        @endforeach

                        </tbody>

                    </table>
                </div>

            </div>

        </div>
    </div>

    {{--{{ $reconcile_logs->links() }}--}}

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

            var table = $('#sale-order-activity-log-datatable').DataTable({
                lengthChange: true,
                paging: true,
                "order": [[ 0, "desc" ]],
                "displayLength": 25,
                // buttons: ['excel'],
                columnDefs: [ {
                    // "targets": [6],
                    // "orderable": false
                } ]
            });

            table.buttons().container().appendTo('#datatable-buttons');
        } );

    </script>

@endsection