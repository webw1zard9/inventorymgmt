@extends('layouts.app')

@section('content')

    <h1 class="header-title">
        Unique Package ID: <a href="{{ route('batches.show', $batch->id) }}">{{ $batch->ref_number }}</a>

    </h1>

    <h5>{{ $batch->name }} - Unit cost: {{ display_currency($batch->unit_price) }}</h5>

    @if(!$batch->wt_based)
    <h6>Uom: {{ $batch->uom }}</h6>
    @endif

    <h6>{{ $batch->transfer_logs->sum('quantity_transferred') }}
        @if($batch->wt_based)
            g
        @else
        {{ $batch->uom }}
        @endif

        packaged
        {{--({{ display_currency($batch->transfer_logs->sum('quantity_transferred') * $batch->unit_price) }})--}}
    </h6>
    <h6>Total Inventory Loss: {{ display_currency($batch->transfer_logs->sum('inventory_loss')) }}

    @if($batch->transfer_logs->sum('inventory_loss')>0 && $batch->unit_price>0)
        <small>({{ number_format(($batch->transfer_logs->sum('inventory_loss') / ($batch->transfer_logs->sum('quantity_transferred') * $batch->unit_price)) * 100 , 2) }}%)</small>
    @endif

    </h6>
    <h6>Total Inventory Loss Grams: {{ $batch->transfer_logs->sum('inventory_loss_grams') }}g</h6>
    <h6>Total Shortage: {{ display_currency($batch->transfer_logs->sum('shortage')) }}
    <h6>Total Shortage Grams: {{ $batch->transfer_logs->sum('shortage_grams') }}g</h6>

<br>
<br>
<br>
    <div class="row">

    @foreach($batch->transfer_logs as $transfer_log)

            <div class="col-4">
                <div class="card-box">

                    @if($transfer_log->canUndo)
                    {{ Form::open(['class'=>'form-horizontal', 'url'=>route('batches.transfer-log', [$batch->id, $transfer_log->id]), 'method'=>'post']) }}

                    <button type="submit" class="btn btn-primary waves-effect waves-light pull-right">Undo</button>

                    {{ Form::close() }}

                    @endif

                    <p>Id: {{ $transfer_log->id }}<br>Entered by <b>{{ $transfer_log->user->name }}</b> on <b>{{ ($transfer_log->created_at->format('m/d/Y H:i:s')) }}</b><br>
                    Packaged by: <b>{{ $transfer_log->packer_name }}</b></p>



                    @if($batch->wt_based)
                        <h6>Qty Transferred: {{ $transfer_log->quantity_transferred }} g</h6>
                    @else
                        <h6>Qty Transferred: {{ $transfer_log->quantity_transferred }} {{ $transfer_log->batch_converted->uom }}

                        <small>-
                        @if($transfer_log->batch_converted->uom!='g')
                            ({{ $transfer_log->start_wt_grams }} g)
                        @endif
                            {{ display_currency($transfer_log->quantity_transferred * $batch->unit_price) }}
                        </small>
                        </h6>
                    @endif

                    <h6>Shortage: {{ display_currency($transfer_log->shortage) }} <small>({{ $transfer_log->shortage_grams }}g)</small></h6>
                    <h6>Loss: {{ display_currency($transfer_log->inventory_loss) }} <small>({{ $transfer_log->inventory_loss_grams }}g)</small></h6>

                        <ul>
                            @foreach($transfer_log->transfer_log_details as $transfer_log_detail)

                                <li style="border-top: solid 1px #ccc;">#{{ $loop->iteration }} - {{ $transfer_log_detail->action }}:

                                    <a href="{{ route('batches.show', $transfer_log_detail->batch_created->ref_number) }}">{{ $transfer_log_detail->units }} {{ $transfer_log_detail->batch_created->uom }}</a>

                                    @if($transfer_log_detail->batch_created->wt_based)
                                        <span class="small">({{ $transfer_log_detail->batch_created->wt_grams }}g)</span>
                                    @endif

                                    {{ ($transfer_log_detail->batch_created->packaged_date ? ' - Packaged: '.$transfer_log_detail->batch_created->packaged_date->format(config('inventorymgmt.date_format')) : '' ) }}
                                        <br> <a href="{{ route('batches.show', $transfer_log_detail->batch_created->ref_number) }}">{{ $transfer_log_detail->batch_created->ref_number }}
                                    </a><br>

                                    Avail Inv: {{ $transfer_log_detail->batch_created->inventory }} {{ $transfer_log_detail->batch_created->uom }}
                                    @if($transfer_log_detail->batch_created->wt_based)
                                        / {{ $transfer_log_detail->batch_created->wt_grams }} g
                                        @if($transfer_log_detail->batch_created->wt_grams != config('inventorymgmt.uom.'.$transfer_log_detail->batch_created->uom))
                                            <span class="small"><i>(Partial)</i></span>
                                        @endif
                                    @endif
                                </li>

                            @endforeach
                        </ul>

                </div>
            </div>

        @endforeach
    </div>


@endsection