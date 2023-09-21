@extends('layouts.app')

@section('content')

    <h2>Hello, {{ Auth::user()->name }}</h2>

    <div class="row">
        <div class="col-lg-12">

        </div>
    </div>


@endsection
