@extends('layouts.app')

@section('content')

    @if(Auth::user()->isAdmin() || Auth::user()->hasRole('locationmanager'))

        @include('dashboard._admin')

    @endif

@endsection


