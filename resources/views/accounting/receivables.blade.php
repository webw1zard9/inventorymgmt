@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <a class="btn btn-primary m-b-5" href="{{ route('accounting.receivables_aging') }}">Aging</a>

            <div class="card-box">

                <h4 class="header-title">Total Receivables: {{ display_currency($all_customers->sum('outstanding_balance')) }}</h4>

                <ul class="nav nav-tabs">

                    <li class="nav-item">
                        <a href="#bulk" data-toggle="tab" aria-expanded="false" class="nav-link active">
                            Bulk
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#packaged" data-toggle="tab" aria-expanded="false" class="nav-link">
                            Packaged
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#other" data-toggle="tab" aria-expanded="true" class="nav-link">
                            Other
                        </a>
                    </li>
                </ul>
                <div class="tab-content">

                    <div class="tab-pane fade active show" id="bulk" aria-expanded="false">

                        <h4 class="header-title">Total: {{ display_currency($bulk_customers->sum('outstanding_balance')) }}</h4>
                        @include('accounting._receivables', ['tab'=>'bulk', 'customers'=> $bulk_customers])

                    </div>
                    <div class="tab-pane fade" id="packaged" aria-expanded="false">

                        <h4 class="header-title">Total: {{ display_currency($packaged_customers->sum('outstanding_balance')) }}</h4>
                        @include('accounting._receivables', ['tab'=>'packaged', 'customers'=> $packaged_customers])

                    </div>
                    <div class="tab-pane fade" id="other" aria-expanded="false">
                        <h4 class="header-title">Total: {{ display_currency($other_customers->sum('outstanding_balance')) }}</h4>
                        @include('accounting._receivables', ['tab'=>'other', 'customers'=> $other_customers])

                    </div>
                </div>
            </div>

        </div>
    </div>

@endsection