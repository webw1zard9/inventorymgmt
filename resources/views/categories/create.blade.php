@extends('layouts.app')


@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                {{ Form::open(['class'=>'form-horizontal', 'url'=>route('categories.store')]) }}

                @include('categories._form')

                <button class="btn btn-primary waves-effect waves-light w-md" type="submit">Save</button>

                {{ Form::close() }}

            </div>
        </div>
    </div>


@endsection