@extends('layouts.app')

@section('content')

    <div class="row mb-2">
        <div class="col-6">

        </div>
        <div class="col-6">
            <a href="{{ route('vendors.statement', ['vendor'=>$vendor, 'download_pdf'=>1]) }}" class="pull-right btn btn-primary">PDF <i class="ion-document-text"></i></a>

        </div>
    </div>

    <h1 class="m-b-7"><a href="{{ route('vendors.show', $vendor) }}">{{ $vendor->name }}</a></h1>
    <h3>Balance: {{ display_currency($final_balance) }}</h3>

    <div class="row">
        <div class="col-lg-12">

            <div class="card-box">

                {{ Form::open(['url' => url()->current(), 'method' => 'get']) }}

                <div class="row">

                    <div class="col-12 col-lg-4 col-xl-2">
                        @include('_partials._preset_date')
                    </div>

                    <div class="col-6 col-md-4 col-xl-2 custom_date_range">
                        @include('_partials._from_date')
                    </div>

                    <div class="col-6 col-md-4 col-xl-2 custom_date_range">
                        @include('_partials._to_date')
                    </div>

                </div>

                <div class="row">
                    <div class="col-md-2 col-xl-3">
                        <button type="submit" class="btn btn-primary waves-effect waves-light">Run Report</button>
                    </div>
                </div>

                {{ Form::close() }}

            </div>
        </div>
    </div>

    <h5>Report Period: {{ Carbon\Carbon::parse($from)->format(config('inventorymgmt.date_format')) }} - {{ Carbon\Carbon::parse($to)->format(config('inventorymgmt.date_format')) }}</h5>
    <div class="row">
        <div class="col-12">
            <div class="card-box">

                @include('users.vendors._statement_table')

            </div>
        </div>
    </div>

@endsection

@section('js')

    <script type="text/javascript">

        $(document).ready(function() {

            $('#date_preset').change(function (e) {

                var from = $('#date_preset option:selected').data('date-from');
                var to = $('#date_preset option:selected').data('date-to');

                if(from) $('#from').val(from);
                if(to) $('#to').val(to);

            });
        });

    </script>

@endsection