@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <h2>{{ $batch->category->name }}: <a href="{{ route('batches.show', $batch->id) }}">{!! $allocate_from_location->batch_location_aggregate->location_batch_name !!}</a> <small>({{ $batch->ref_number }})</small></h2>

                <h4>Available to Allocate from location {{ $allocate_from_location->name }}:<br><br>
                    <span class="available_inventory">
                        {{ floatval($allocate_from_location->batch_location_aggregate->available_inventory) }}
                    </span> {{ $batch->uom }} @ {{ display_currency($allocate_from_location->batch_location_aggregate->location_unit_price) }}
                </h4>
<br>
<br>
                @if($locations->count())
                {{ Form::model($batch, ['class'=>'form-horizontal', 'url'=>route('batches.allocate-store', [$batch->id, $allocate_from_location->id])]) }}

                <table id="inventory" class="table">
                    <thead>
                    <tr>
                        <th>Location</th>
                        <th>Remaining Allocated Qty</th>
                        <th>Qty</th>
                        <th>Name</th>
                        <th>Cost</th>
                        <th>Price</th>
                        <th>Min. Flex</th>
                    </tr>
                    </thead>

                    <tbody>

                    @foreach($locations as $location)

                        <tr>
                            <td>
                                {{ $location->name }}
                                {{ Form::hidden("locations[".$location->id."][location_name]", $location->name) }}
                            </td>
                            <td>{{ ($existing_allocated_locations[$location->id]->batch_location_aggregate->available_inventory ?? "0") }} {{ $batch->uom }}</td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control allcoated_qty" name="locations[{{ $location->id }}][quantity]" value="">
                                    <span class="input-group-addon uom">{{ $batch->uom }}</span>
                                </div>
                            </td>
                            <td>
                                @if(!empty($existing_allocated_locations[$location->id]))
                                    {{ Form::text("locations[".$location->id."][name]", $existing_allocated_locations[$location->id]->batch_location_aggregate->location_batch_name??$batch->name, ['class'=>'form-control', 'disabled'=>'disabled']) }}
                                    {{ Form::hidden("locations[".$location->id."][name]", $existing_allocated_locations[$location->id]->batch_location_aggregate->location_batch_name??$batch->name) }}
                                @else
                                    {{ Form::text("locations[".$location->id."][name]", $allocate_from_location->batch_location_aggregate->location_batch_name, ['class'=>'form-control']) }}
                                @endif
                            </td>
                            <td>

                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    {{ Form::text("locations[".$location->id."][unit_price]",
                                           (
                                               ! empty($existing_allocated_locations[$location->id]) &&
                                               $existing_allocated_locations[$location->id]->batch_location_aggregate->location_unit_price
                                           ) ?
                                           $existing_allocated_locations[$location->id]->batch_location_aggregate->location_unit_price :
                                           $allocate_from_location->batch_location_aggregate->location_unit_price,
                                           ['class'=>'form-control','placeholder'=>'Unit Cost', 'disabled'=>'disabled']) }}
                                    {{ Form::hidden("locations[".$location->id."][unit_price]", ( $existing_allocated_locations[$location->id]->batch_location_aggregate->location_unit_price ?? $allocate_from_location->batch_location_aggregate->location_unit_price)) }}
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    @if(!empty($existing_allocated_locations[$location->id]))
                                        {{ Form::text("locations[".$location->id."][suggested_unit_sale_price]", $existing_allocated_locations[$location->id]->batch_location_aggregate->suggested_unit_sale_price, ['class'=>'form-control','placeholder'=>'Sugg. Unit Price', 'disabled'=>'disabled']) }}
                                        {{ Form::hidden("locations[".$location->id."][suggested_unit_sale_price]", $existing_allocated_locations[$location->id]->batch_location_aggregate->suggested_unit_sale_price) }}
                                    @else
                                        {{ Form::text("locations[".$location->id."][suggested_unit_sale_price]", $batch->suggested_unit_sale_price, ['class'=>'form-control','placeholder'=>'Sugg. Unit Price']) }}
                                    @endif

                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    @if(!empty($existing_allocated_locations[$location->id]))
                                        {{ Form::text("locations[".$location->id."][min_flex]", $existing_allocated_locations[$location->id]->batch_location_aggregate->min_flex, ['class'=>'form-control','placeholder'=>'Min. Flex', 'disabled'=>'disabled']) }}
                                        {{ Form::hidden("locations[".$location->id."][min_flex]", $existing_allocated_locations[$location->id]->batch_location_aggregate->min_flex) }}
                                    @else
                                        {{ Form::text("locations[".$location->id."][min_flex]", $batch->min_flex, ['class'=>'form-control','placeholder'=>'Min. Flex']) }}
                                    @endif
                                </div>
                            </td>


                        </tr>

                    @endforeach

                    </tbody>

                </table>

                <button class="btn btn-primary waves-effect waves-light" type="submit">Allocate</button>

                {{ Form::close() }}

                @else
                <h3 class="text-danger">No active locations to allocated to</h3>
                @endif

            </div>
        </div>
    </div>


@endsection

@section('js')

    <script type="text/javascript">

        $(document).ready(function() {

        });

    </script>

@endsection