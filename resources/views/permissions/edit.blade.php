@extends('layouts.app')


@section('content')

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                {{ Form::model($permission, ['class'=>'form-horizontal', 'url'=>route('permissions.update', $permission->id)]) }}

                {{ method_field('PUT') }}

                @include('permissions._form')

                <button class="btn btn-primary waves-effect waves-light w-md" type="submit">Save</button>

                {{ Form::close() }}

            </div>
        </div>
    </div>

@endsection