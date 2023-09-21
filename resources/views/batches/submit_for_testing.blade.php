@extends('layouts.app')

@section('content')

    <h1 class="header-title">Submit Sample for Testing</h1>

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                <h3>{{ $batch->category->name }}: {{ $batch->name }}</h3>
                <h6>Metrc/UID: {{ $batch->ref_number }}</h6>
                <h6>Batch Size: {{ $batch->inventory }} {{ $batch->uom }}</h6>

                <hr>

                <h1 class="header-title">Create Sample</h1>
                {{ Form::open(['action'=>'post', 'class'=>'form-horizontal', 'url'=>route('batches.submit_for_testing', $batch->id)]) }}

                <div class="row">

                    <div class="col-md-4">

                        <div class="form-group">

                            {{ Form::label('sample_size', 'Sample Size') }}
                            <div class="input-group bootstrap-touchspin">

                            <input id="sample_weight" type="text" value="{{ old("sample_weight") }}" name="sample_weight" placeholder="0" class="form-control col-lg-3" required="required">
                            <span class="input-group-addon bootstrap-touchspin-postfix">g</span>
                            </div>
                        </div>

                        <div class="form-group">
                            {{ Form::label('ref_number', 'METRC/UID') }}
                            {{ Form::text('ref_number', old('ref_number'), array('class' => 'form-control', 'placeholder' => 'METRC/UID', 'required'=>'required')) }}
                        </div>

                        <div class="form-group">
                            {{ Form::label('testing_lab', 'Testing Lab') }}
                            {{ Form::select('testing_laboratory_id', $testing_laboratories, old('testing_laboratory_id'), ['class'=>'form-control']) }}

                            {{--<select name="testing_laboratory_id" id="testing_laboratory_id" class="form-control">--}}
                                {{--<option value="">-- Select --</option>--}}
                                {{--@foreach($testing_laboratories as $testing_laboratory)--}}
                                    {{--<option value="{{ $testing_laboratory->id }}" {{ ($testing_laboratory->id == $batch->testing_laboratory_id?"selected='selected'":"") }}>{{ $testing_laboratory->name }} (Lic# {{ $testing_laboratory->details['lab_license_number'] }})</option>--}}
                                {{--@endforeach--}}
                            {{--</select>--}}
                        </div>

                        <div class="form-group">
                            {{ Form::label('packaged_date', 'Packaged Date', ['class'=>'col-form-label']) }}
                            {{ Form::date("packaged_date", old("packaged_date", \Carbon\Carbon::now()), ['class'=>'form-control', 'required'=>'required']) }}
                        </div>

                        <div class="form-group">
                            <button class="btn btn-primary waves-effect waves-light w-md" type="submit">Submit</button>
                            <div class="clearfix"></div>
                        </div>

                    </div>


                </div>



                {{ Form::close() }}


            </div>
        </div>
    </div>
@endsection

