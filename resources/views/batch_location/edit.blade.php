@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <h2>{{ $batch->category->name }}: {!! $batch->present()->branded_name !!}
                    @if(Auth::user()->isAdmin() && $batch->original_name)<small>({{ $batch->original_name }})</small>@endif
                </h2>
                <h3>Location: {{ $location->name }}</h3>
                <h4>Remaining Location Qty: <span class="text-success">{{ ($batch->track_inventory ? floatval($location->remainingInventory($batch->id))." ".$batch->uom : "Unlimited") }}</span></h4>

                <div class="row">

                    <div class="col-xl-6 col-lg-6">

                        {{ Form::model($batchLocation, ['class'=>'form-horizontal', 'url'=>route('batch-location.update', $batchLocation->id)]) }}
                        {{ Form::hidden('_edit_batch_location',true) }}
                        {{ method_field('PUT') }}

                        <div class="form-group">
                            {{ Form::label('name', 'Name') }}
                            {{ Form::text('name', old('name'), array('class' => 'form-control', 'placeholder' => 'Name')) }}
                        </div>

                        @can('batches.show.cost')

                        @if($batch->track_inventory && floatval($location->remainingPoInventory($batch->id)) <= 0)
                            <div class="help-block text-danger"><small>Unable to change cost. There is no remaining inventory.</small></div>
                        @endif

                        <div class="form-group row">

                            <div class="col-6">
                                {{ Form::label('unit_price', 'Change cost on this many units:') }}
                                <div class="input-group">

                                    @if(floatval($location->remainingPoInventory($batch->id)) <= 0)
                                        {{ Form::text("qty_to_change_unit_cost", null, ['class'=>'form-control','placeholder'=>'', 'disabled'=>'disabled']) }}
                                    @else
                                        {{ Form::text("qty_to_change_unit_cost", null, ['id'=>'qty_to_change_unit_cost', 'class'=>'form-control','placeholder'=>'', 'min'=>'0', 'max'=>$batch->units_purchased]) }}
                                    @endif
                                    <span class="input-group-addon">{{ $batch->uom }}</span>
                                </div>
                                <div class="help-block text-danger"><small>Maximum: <strong>{{ $batch->units_purchased }} {{ $batch->uom }}</strong></small></div>

                            </div>
                            <div class="col-6">

                                <div class="form-group">
                                    {{ Form::label('unit_price', 'Change unit cost to:') }}
                                    <div class="input-group">
                                        <span class="input-group-addon">$</span>
                                        @if(floatval($location->remainingPoInventory($batch->id)) <= 0)
                                            {{ Form::text("new_unit_cost", null, ['class'=>'form-control','placeholder'=>'', 'disabled'=>'disabled']) }}
                                        @else
                                            {{ Form::text("new_unit_cost", $batch->unit_price, ['id'=>'unit_price', 'class'=>'form-control','placeholder'=>'']) }}
                                            <input type="hidden" id="current_avg_price" value="{{ $batch->unit_price }}" />
                                        @endif
                                    </div>

                                    <span class="help-block text-danger"><small>Current average cost per unit: <strong>{{ display_currency($batch->unit_price) }}</strong></small></span>
                                    <span class="help-block text-danger"><small>Total cost change: <span id="total_cost_change"><strong>$0</strong></span></small></span>


                                </div>

                            </div>
                        </div>


                        @endcan

                        <div class="form-group">
                            {{ Form::label('suggested_unit_sale_price', 'Suggested Unit Sale Price') }}
                            <div class="input-group">
                                <span class="input-group-addon">$</span>
                                {{ Form::text("suggested_unit_sale_price", old('suggested_unit_sale_price'), ['class'=>'form-control','placeholder'=>'']) }}
                            </div>
                        </div>

                        <div class="form-group">
                            {{ Form::label('min_flex', 'Min. Flex') }}
                            <div class="input-group">
                                <span class="input-group-addon">$</span>
                                {{ Form::text("min_flex", old('min_flex'), ['class'=>'form-control','placeholder'=>'']) }}
                            </div>
                        </div>

                        <button class="btn btn-primary waves-effect waves-light w-md" type="submit">Save</button>

                        {{ Form::close() }}

                    </div>

                </div>
        </div>
    </div>

@endsection

@section('js')

    <script type="text/javascript">
        $(document).ready(function() {

            $('#unit_price, #qty_to_change_unit_cost').change(function () {

                var qty_change = parseFloat($('#qty_to_change_unit_cost').val());
                var current_cost = parseFloat($('#current_avg_price').val());
                var new_cost = parseFloat($('#unit_price').val());

                var unit_cost_change = (new_cost - current_cost);

                console.log(current_cost);
                console.log(new_cost);
                console.log(unit_cost_change);
                console.log(unit_cost_change * qty_change);
                console.log($(this));

                $('#total_cost_change').html("<strong>$"+parseFloat(unit_cost_change * qty_change).toFixed(2) + "</strong>");

            });

        });

    </script>

@endsection

