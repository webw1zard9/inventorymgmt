@extends('layouts.app')


@section('content')

    <h1 class="header-title">Update User - {{ $active_role->description }}</h1>

    <div class="row">
        <div class="col-lg-12">
            <div class="card-box">

                {{ Form::model($user, ['class'=>'form-horizontal', 'url'=>route('users.update', $user->id)]) }}

                {{ method_field('PUT') }}

                @include('users._form')

                <button class="btn btn-primary waves-effect waves-light w-md" type="submit">Save</button>

                {{ Form::close() }}

            </div>
        </div>
    </div>

@endsection