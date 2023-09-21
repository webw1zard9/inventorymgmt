@extends('layouts.app')


@section('content')

<div class="row">
    <div class="col-lg-12">
        <div class="card-box">
            <div class="table-responsive">

                <table id="activity-log-datatable" class="table table-hover table-striped">

                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Log Name</th>
                        <th>Description</th>
                        <th>Subject</th>
                        <th>Causer</th>
                        <th>Occured</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($activity_logs as $activity_log)
{{--{{ dump($activity_log) }}--}}
                        <tr>
                            <td>{{ $activity_log->id }}</td>
                            <td>{{ $activity_log->log_name }}</td>
                            <td>{{ $activity_log->description }}</td>
                            <td>{{ get_class($activity_log->subject) }}</td>
                            <td>{{ dump($activity_log->changes()) }}</td>
                            <td>{{ $activity_log->causer->name }}</td>
                            <td>{{ $activity_log->created_at->format('m/d/y H:i') }}</td>
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

            // $('[type="date"]').datepicker();

            var table = $('#activity-log-datatable').DataTable({
                lengthChange: true,
                paging: true,
                "order": [[ 1, "asc" ]],
                "displayLength": 25,
                buttons: ['excel'],
                columnDefs: [ {
                    "targets": [6],
                    "orderable": false
                } ]
            });

            table.buttons().container().appendTo('#datatable-buttons');
        } );

    </script>

@endsection