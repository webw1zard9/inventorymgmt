@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12">
            @if(Auth::user()->isSuperAdmin())
                <a href="{{ route('permissions.create') }}" class="btn btn-primary waves-effect waves-light mb-2 pull-right">Create Permission</a>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <div class="table-responsive">
                    <table id="permissions-datatable" class="table table-hover">

                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Permission Key</th>
                                <th style="width: 10%; white-space: nowrap"></th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($permissions as $permission)
                                <tr>
                                    <td>
                                        {{ $permission->description }}
                                    </td>
                                    <td>
                                        {{ $permission->name }}
                                    </td>

                                    <td style="text-align: right;">

                                        @if($permission->canDelete())
                                        <form action="{{ route('permissions.destroy', $permission->id) }}" method="POST">
                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}

                                            <a href="{{ route('permissions.edit', $permission) }}" class="btn btn-secondary btn"><i class="ion-edit"></i></a>


                                            <button type="submit" class="btn btn-danger"><i class="ion-trash-a"></i></button>

                                        </form>
                                        @endif

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

{{--    <script type="text/javascript">--}}
{{--        $(document).ready(function() {--}}

{{--            $.fn.dataTable.moment('MM/DD/YYYY');--}}

{{--            var table = $('#brands-datatable').DataTable({--}}
{{--                lengthChange: true,--}}
{{--                paging: true,--}}
{{--                "order": [[ 0, "asc" ]],--}}
{{--                "displayLength": 100,--}}
{{--                buttons: ['excel', 'pdf', 'colvis']--}}
{{--            });--}}

{{--            table.buttons().container().appendTo('#datatable-buttons');--}}

{{--        } );--}}

{{--    </script>--}}


@endsection