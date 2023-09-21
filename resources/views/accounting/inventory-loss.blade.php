@extends('layouts.app')


@section('content')

    <div class="row">


            @foreach($inventory_loss->groupBy('packed_month_year') as $packed_month_year=>$rows0)
            <div class="col-xl-3">

                <div class="card">

                    <div class="card-header">
                        <h4 class="">{{ \Carbon\Carbon::createFromFormat('Y-d-m',$packed_month_year)->format('M, Y') }}</h4>
                    </div>

                    <div class="card-block">

                        <div class="row">

                            {{--{{ dd($rows0) }}--}}

{{--                        @foreach($rows0->groupBy('fund_name') as $fund_name=>$rows)--}}

                        <div class="col-lg-12">
                            {{--<h4 class="card-title" style="border-bottom: 1px solid #ccc;">{{ ($fund_name?:'Misc.') }}</h4>--}}


                            @foreach($rows0->groupBy('type') as $name=>$row2)

                                @if($name=='Pre-Pack')
                                    <h6 class="">{{ $name }}: {{ display_currency($row2->sum('inventory_loss')+$row2->sum('shortage')) }}</h6>

                                    <ul>
                                        @if($row2->first()->shortage)
                                        <li>Shortage: {{ display_currency($row2->first()->shortage) }}</li>
                                        @endif
                                        <li>Loss: {{ display_currency($row2->first()->inventory_loss) }}</li>
                                    </ul>

                                @else

                                    <h6 class="">{{ $name }}: {{ display_currency($row2->sum('inventory_loss')) }}</h6>
                                    <ul>
                                    @foreach($row2 as $row)

                                    <li>{{ $row->reason }}: {{ display_currency($row->inventory_loss) }}</li>
                                    @endforeach
                                    </ul>
                                @endif

                            @endforeach
                            {{--</ul>--}}
                            {{--<hr>--}}
                            {{--<h4 class="card-title">Fund Total: {{ display_currency($rows->sum('inventory_loss') + $rows->sum('shortage')) }}</h4>--}}
                        </div>

                        {{--@endforeach--}}

                        </div>

                    </div>

                    <div class="card-footer text-muted">
                        <h4 class="card-title">Month Total: {{ display_currency($rows0->sum('inventory_loss') + $rows0->sum('shortage')) }}</h4>
                    </div>
                </div>
            </div>
            @endforeach

    </div>

@endsection