@extends('layouts.app')


@section('content')

    <div class="row">
        <div class="col-lg-12">
                <a href="{{ route('locations.create') }}" class="btn btn-primary waves-effect waves-light mb-2 pull-right">Create Location</a>
        </div>

    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <div class="table-responsive">
                    <table id="locations-datatable" class="table table-hover">

                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Address2</th>
                                <th>Active</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($locations as $location)

                                <tr>
                                    <td>
                                        <a href="">{{ $location->name }}<small><i>{{ ($location->trashed()?"(Deleted)":"") }}</i></small></a>
                                    </td>
                                    <td>
                                        {{ $location->address }}
                                    </td>
                                    <td>
                                        {{ $location->address2 }}
                                    </td>
                                    <td>
                                        @if($location->active)
                                            <i class="mdi mdi-check text-success font-16"></i>
                                            @else
                                            <i class=" mdi mdi-window-close text-danger font-16"></i>
                                        @endif

                                    </td>
                                    <td style="text-align: right;">

                                        @if($location->trashed())
                                            <form action="{{ route('locations.restore', $location->id) }}" method="POST" class="pull-right">
                                                {{ method_field('PUT') }}
                                                {{ csrf_field() }}
                                                <button class="btn btn-success">Restore</button>
                                            </form>
                                        @else
                                            <form action="{{ route('locations.destroy', $location->id) }}" method="POST" class="pull-right">
                                                {{ method_field('DELETE') }}
                                                {{ csrf_field() }}
                                                <button class="btn btn-danger"{{ ($location->hasInventory()?"disabled='disabled'":"") }}>
                                                    <i class="ion-trash-a" {{ ($location->hasInventory()?'data-toggle=tooltip':"") }} data-placement="left" title="" data-original-title="This location has inventory and cannot be deleted."></i>
                                                </button>
                                            </form>
                                        @endif

                                            <a href="{{ route('locations.edit', ['location'=>$location->id]) }}" class="btn btn-secondary btn pull-right mr-2"><i class="ion-edit"></i></a>



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
        // $(document).ready(function() {
        //
        //     $.fn.dataTable.moment('MM/DD/YYYY');
        //
        //     var table = $('#po-datatable').DataTable({
        //         lengthChange: true,
        //         paging: true,
        //         "order": [[ 0, "desc" ]],
        //         "displayLength": 25,
        //         buttons: ['excel', 'pdf', 'colvis']
        //     });
        //
        //     table.buttons().container().appendTo('#datatable-buttons');
        //
        // } );

    </script>


@endsection