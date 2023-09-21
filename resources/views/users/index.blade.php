@extends('layouts.app')


@section('content')
<h3>Results: {{ $users->count() }}</h3>

<div class="row">
        <div class="col-lg-12">
            <div class="card-box">
                <div class="table-responsive">

                    <table id="user-datatable" class="table table-hover">

                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roles</th>
                            @if(collect(['Location Manager', 'Sales Rep'])->contains($role))
                                <th>Locations</th>
                            @endif

                            <th>Active</th>

                            <th>
                                @anygate('users.create', 'users.create.customer')
                                <a href="{{ route('users.create') }}?role={{ $role }}" class="btn btn-primary"><i class="ion-person-add"></i></a>
                                @endanygate
                            </th>
                        </tr>
                        </thead>

                        <tbody>

                        @foreach($users as $user)

                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    <a href="{{ route('users.show', $user->id) }}">{{ $user->name }}</a>
                                    @if(Auth::user()->isAdmin())
                                    <a href="{{ route('users.force-login', $user->id) }}"><i class=" mdi mdi-apple-keyboard-caps"></i></a>
                                    @endif
                                </td>
                                <td>{{ $user->email }}</td>

                                <td>{!! display_roles($user) !!}</td>
                                @if(collect(['Location Manager', 'Sales Rep'])->contains($role))
                                    <td>{!! display_locations($user) !!}</td>
                                @endif

                                <td>
                                    @if($user->active)
                                        <i class="mdi mdi-check text-success font-16"></i>
                                    @else
                                        <i class=" mdi mdi-window-close text-danger font-16"></i>
                                    @endif
                                </td>
                                <td>

                                    @if($user->can_edit_super_user)
                                        @anygate('users.edit', 'users.edit.customer')
                                        <a href="{{ route('users.edit', ['user'=>$user->id]) }}" class="btn btn-secondary"><i class="ion-edit"></i></a>
                                        @endanygate
                                    @endif


                                    @if(!$user->super_user)

                                    @can('users.delete.customer')
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST" style="display: inline-block;">
                                            {{ method_field('DELETE') }}
                                            {{ csrf_field() }}
                                            <button type="submit" class="btn btn-danger waves-effect"><i class="ion-trash-a"></i></button>
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