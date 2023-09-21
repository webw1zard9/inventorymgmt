@extends('layouts.app')


@section('content')

    <h1 class="header-title">Update Location</h1>

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                {{ Form::model($location, ['class'=>'form-horizontal', 'url'=>route('locations.update', $location->id)]) }}

                {{ method_field('PUT') }}

                @include('locations._form')

                <button class="btn btn-primary waves-effect waves-light w-md" type="submit">Save</button>

                {{ Form::close() }}

            </div>
        </div>
    </div>

@endsection