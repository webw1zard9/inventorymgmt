@extends('layouts.app')

@section('content')

    @can('batches.show')
        <a href="{{ route('batches.show', $batch->id) }}" class="btn btn-primary waves-effect waves-light m-b-10">Back</a>
    @endcan

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                {{ Form::model($batch, ['class'=>'form-horizontal', 'url'=>route('batches.update', $batch->id)]) }}

                {{ method_field('PUT') }}

                <div class="row">

                    <div class="col-xl-7">
                        <h2>{{ $batch->category->name }}: {{ $batch->present()->branded_name }}</h2>

                        @include('batches._batch_info', $batch)

                        <input type="hidden" name="status" value="Inventory" />

                        <dl class="row">

                            <dt class="col-xl-5 text-xl-right">Brand:</dt>
                            <dd class="col-xl-5">
                                {{ Form::select('brand_id', $brands->pluck('name','id')->toArray(), null, ['placeholder' => '- Select -','class'=>'form-control']) }}
                            </dd>

                            <dt class="col-xl-5 text-xl-right">Category:</dt>
                            <dd class="col-xl-5">
                            {{ Form::select('category_id', $categories->pluck('name','id')->toArray(), null, ['class'=>'form-control']) }}
                            </dd>

                            <dt class="col-xl-5 text-xl-right">Name:</dt>
                            <dd class="col-xl-5"><input type="text" class="form-control" id="name" name="name" value="{{ $batch->getRawOriginal('name') }}"></dd>

                            <dt class="col-xl-5 text-xl-right">Type:</dt>
                            <dd class="col-xl-5">
                                {{ Form::select('type', collect(config('inventorymgmt.product_type'))->combine(config('inventorymgmt.product_type'))->prepend('-- Select --',''), $batch->type, ['class'=>'form-control']) }}
                            </dd>

                        </dl>

{{--                        <dl class="row">--}}

{{--                            <dt class="col-xl-5 text-xl-right">Sugg. Sale Price:</dt>--}}
{{--                            <dd class="col-xl-5">--}}
{{--                                <div class="input-group bootstrap-touchspin">--}}
{{--                                    <span class="input-group-addon bootstrap-touchspin-prefix">$</span>--}}
{{--                                    <input id="suggested_unit_sale_price" type="number" value="{{ ($batch->suggested_unit_sale_price?:'') }}" name="suggested_unit_sale_price" class="form-control" style="display: block;" placeholder="Sugg. Sale Price" step="0.01">--}}
{{--                                </div>--}}
{{--                            </dd>--}}

{{--                            <dt class="col-xl-5 text-xl-right">Min. Flex:</dt>--}}
{{--                            <dd class="col-xl-5">--}}
{{--                                <input type="text" class="form-control" id="min_flex" name="min_flex" value="{{ ($batch->min_flex?:'') }}" placeholder="Ex: 100">--}}

{{--                            </dd>--}}
{{--                            --}}
{{--                        </dl>--}}

                    </div>

                    <div class="col-xl-5">
                        <h6>Additional Info</h6>

                        <div class="form-group">
                            {{ Form::textarea('sales_notes', $batch->sales_notes, ['class'=>'form-control', 'rows'=>'4', 'placeholder'=>'Add Sales Notes']) }}
                        </div>

                        @if($batch->character)
                        <h6>Characteristics</h6>
                        {{ implode(", ", (array)$batch->character) }}
                        @endif

                    </div>

                </div>

                <hr>

                <button class="btn btn-primary waves-effect waves-light w-md pull-right" type="submit">Save</button>
                <div class="clearfix"></div>

                {{ Form::close() }}

            </div>
        </div>
    </div>

@endsection