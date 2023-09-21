@extends('layouts.app')


@section('content')

    <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn btn-primary waves-effect waves-light mb-2">Back</a>

    <h4>{{$purchaseOrder->ref_number  }}</h4>

    <h5>{{ $purchaseOrder->batches->count() }} Items</h5>
    <div class="row">
        <div class="col-lg-12">

            {{ Form::open(['class'=>'form-horizontal', 'url'=>route('purchase-orders.allocate-items-store', $purchaseOrder)]) }}

            @foreach($purchaseOrder->batches as $batch)

            <div class="card-box">

                <h2>{{ $batch->category->name }}: <a href="{{ route('batches.show', $batch->id) }}">{!! $batch->present()->branded_name !!}</a> <small>({{ $batch->ref_number }})</small></h2>

                <h4>Available to Allocate from {{ (Auth::user()->hasLocation()?Auth::user()->current_location->name:"Nest") }}:
                    <span class="{{ (!$batch->qty_to_allocate?"text-danger":"") }}">
                        <span class="available_inventory">
                            {{ $batch->qty_to_allocate }}
                        </span>
                        {{ $batch->uom }} @ {{ display_currency($batch->unit_price) }}
                    </span>
                </h4>
{{--{{ dd($batch->cost_by_location) }}--}}
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

                        @continue(Auth::user()->hasLocation() && $location->id == Auth::user()->current_location->id)

                        <tr>

                            <td>
                                {{ $location->name }}
                                {{ Form::hidden("batches[$batch->id][locations][".$location->id."][location_name]", $location->name) }}
                            </td>

                            <td>{{ (!empty($location->batches->groupBy('id')[$batch->id])?$location->batches->groupBy('id')[$batch->id]->sum('batch_location.quantity'):0) }} {{ $batch->uom }}</td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control allcoated_qty" name="batches[{{$batch->id}}][locations][{{ $location->id }}][quantity]" value="{{ old('batches.'.$batch->id.'.locations.'.$location->id.'.quantity') }}">
                                    <span class="input-group-addon uom">{{ $batch->uom }}</span>
                                </div>
                            </td>
                            <td>
                                @if(!empty($location->batches->groupBy('id')[$batch->id]))
                                    {{ Form::text("batches[$batch->id][locations][".$location->id."][name]", old('batches.'.$batch->id.'.locations.'.$location->id.'.name', $location->batches->groupBy('id')[$batch->id]->first()->batch_location->name), ['class'=>'form-control','placeholder'=>'Rename', 'disabled'=>'disabled']) }}
                                    {{ Form::hidden("batches[$batch->id][locations][".$location->id."][name]", $location->batches->groupBy('id')[$batch->id]->first()->batch_location->name) }}
                                @else
                                    {{ Form::text("batches[$batch->id][locations][".$location->id."][name]", $batch->name, ['class'=>'form-control','placeholder'=>'Rename']) }}
                                @endif
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                        {{ Form::text("batches[$batch->id][locations][".$location->id."][unit_price]", (!empty($batch->cost_by_location[$location->id])?$batch->cost_by_location[$location->id]:$batch->unit_price), ['class'=>'form-control','placeholder'=>'Unit Cost', 'disabled'=>'disabled']) }}
                                        {{ Form::hidden("batches[$batch->id][locations][".$location->id."][unit_price]", (!empty($batch->cost_by_location[$location->id])?$batch->cost_by_location[$location->id]:$batch->unit_price)) }}
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    @if(!empty($location->batches->groupBy('id')[$batch->id]))
                                        {{ Form::text("batches[$batch->id][locations][".$location->id."][suggested_unit_sale_price]", old('batches.'.$batch->id.'.locations.'.$location->id.'.suggested_unit_sale_price', $location->batches->groupBy('id')[$batch->id]->first()->batch_location->suggested_unit_sale_price), ['class'=>'form-control','placeholder'=>'Sugg. Unit Price', 'disabled'=>'disabled']) }}
                                        {{ Form::hidden("batches[$batch->id][locations][".$location->id."][suggested_unit_sale_price]", $location->batches->groupBy('id')[$batch->id]->first()->batch_location->suggested_unit_sale_price) }}
                                    @else
                                        {{ Form::text("batches[$batch->id][locations][".$location->id."][suggested_unit_sale_price]", $batch->suggested_unit_sale_price, ['class'=>'form-control','placeholder'=>'Sugg. Unit Price']) }}
                                    @endif

                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    @if(!empty($location->batches->groupBy('id')[$batch->id]))
                                        {{ Form::text("batches[$batch->id][locations][".$location->id."][min_flex]", old('batches.'.$batch->id.'.locations.'.$location->id.'.min_flex', $location->batches->groupBy('id')[$batch->id]->first()->batch_location->min_flex), ['class'=>'form-control','placeholder'=>'Min. Flex', 'disabled'=>'disabled']) }}
                                        {{ Form::hidden("batches[$batch->id][locations][".$location->id."][min_flex]", $location->batches->groupBy('id')[$batch->id]->first()->batch_location->min_flex) }}
                                    @else
                                        {{ Form::text("batches[$batch->id][locations][".$location->id."][min_flex]", $batch->min_flex, ['class'=>'form-control','placeholder'=>'Min. Flex']) }}
                                    @endif
                                </div>
                            </td>

                        </tr>

                    @endforeach

                    </tbody>

                </table>

            </div>

            @endforeach

                <button class="btn btn-primary waves-effect waves-light" type="submit">Allocate</button>
            {{ Form::close() }}
        </div>
    </div>


@endsection
