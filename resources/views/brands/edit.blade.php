@extends('layouts.app')


@section('content')

{{--    <h1 class="header-title">Update Brand</h1>--}}

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                {{ Form::model($brand, ['class'=>'form-horizontal', 'url'=>route('brands.update', $brand->id)]) }}

                {{ method_field('PUT') }}

                @include('brands._form')

                <button class="btn btn-primary waves-effect waves-light w-md" type="submit">Save</button>

                {{ Form::close() }}

            </div>
        </div>
    </div>

@endsection