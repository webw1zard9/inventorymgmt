
@extends('layouts.app')

@section('content')

    <h2>Hello, {{ Auth::user()->name }}</h2>

    @include('dashboard._admin')

@endsection
