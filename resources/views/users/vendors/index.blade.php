@extends('layouts.app')


@section('content')
<h3>Results: {{ $vendors->count() }}</h3>

<div class="row">
        <div class="col-lg-12">
            <div class="card-box">
                <div class="table-responsive">

                    <table id="user-datatable" class="table table-hover">

                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>PO Balances</th>
                            <th>Vendor Balance</th>
                            <th>Active</th>
                            <th>
                                @can('users.create.vendor')
                                <a href="{{ route('users.create') }}?role={{ $role }}" class="btn btn-primary"><i class="ion-person-add"></i></a>
                                @endcan
                            </th>
                        </tr>
                        </thead>

                        <tbody>

                        @foreach($vendors as $vendor)

                            <tr>
                                <td>{{ $vendor->id }}</td>
                                <td>
                                    <a href="{{ route('vendors.show', $vendor->id) }}">{{ $vendor->name }}</a>
                                    @if(Auth::user()->isAdmin())
                                    <a href="{{ route('users.force-login', $vendor->id) }}"><i class=" mdi mdi-apple-keyboard-caps"></i></a>
                                    @endif
                                </td>
                                <td>{{ display_currency($vendor->purchase_orders->sum('balance')) }}</td>
                                <td>{{ display_currency($vendor->balance) }}</td>
                                <td>
                                    @if($vendor->active)
                                        <i class="mdi mdi-check text-success font-16"></i>
                                    @else
                                        <i class=" mdi mdi-window-close text-danger font-16"></i>
                                    @endif
                                </td>
                                <td>
                                    @if($vendor->can_edit_super_user)
                                        @can('users.edit.vendor')
                                        <a href="{{ route('users.edit', ['user'=>$vendor->id]) }}" class="btn btn-secondary"><i class="ion-edit"></i></a>
                                        @endcan
                                    @endif

                                    @if(!$vendor->super_user)

                                        @can('users.delete.customer')
                                            <form action="{{ route('users.destroy', $vendor->id) }}" method="POST" style="display: inline-block;">
                                                {{ method_field('DELETE') }}
                                                {{ csrf_field() }}
                                                <button type="submit" class="btn btn-danger waves-effect"><i class="ion-trash-a"></i></button>
                                                {{--<a href="{{ route('users.destroy', ) }}" class="btn btn-danger"></a>--}}
                                            </form>
                                        @endcan

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

            var table = $('#user-datatable').DataTable({
                lengthChange: true,
                paging: true,
                "order": [[ 1, "asc" ]],
                "displayLength": 25,
                buttons: ['excel'],
                columnDefs: [ {
                    "targets": [$('#user-datatable thead tr').children('th').length - 1],
                    "orderable": false
                } ]
            });

            table.buttons().container().appendTo('#datatable-buttons');
        } );

    </script>

@endsection