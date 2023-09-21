@extends('layouts.app')



@section('content')

    <h3>Locations Owe</h3>
    <div class="row">

        @if(count($location_aggregate_owed) > 1)
        <div class="col-md-3  col-sm-12">
            <div class="card m-b-20 card-block">
                <h3 class="mt-0 card-title">Total<small class="pull-right">{{ display_currency(array_sum($location_aggregate_owed)) }}</small></h3>
            </div>
        </div>
        @endif

        @foreach($location_aggregate_owed as $location_name => $total_owed)
            @if($total_owed)
            <div class="col-md-3 col-sm-12">
                <div class="card m-b-20 card-block">
                    <h3 class="mt-0 card-title">{{ $location_name }}<small class="pull-right">{{ display_currency($total_owed) }}</small></h3>
                </div>
            </div>
            @endif
        @endforeach

    </div>

    <div class="row">
        <div class="col-lg-12">

            <div class="card-box">

                @foreach($vendors as $vendor)

                    @if(!$show_all)
                        @continue($vendor->purchase_orders->sum('total_owed')==0)
                    @endif

                    @if($vendor->purchase_orders->count())

                    <div class="card m-b-20">
                        <div class="card-header" id="heading-{{ $vendor->id }}" data-toggle="collapse" data-target="#collapse-{{ $vendor->id }}" style="cursor: pointer">

                            <h4>{{ $vendor->name }}: {{ display_currency($vendor->purchase_orders->sum('total_owed')) }}</h4>
                        </div>

                        <div id="collapse-{{ $vendor->id }}" class="card-block">

                            @foreach($vendor->purchase_orders as $purchase_order)

                                @if(!$show_all)
                                    @continue($purchase_order->total_owed==0)
                                @endif

                                @include('accounting._payables_po')

                            @endforeach


                        </div>
                    </div>

                    @endif

                @endforeach

            </div>
        </div>
    </div>

@endsection