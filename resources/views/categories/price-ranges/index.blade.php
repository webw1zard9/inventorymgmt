@extends('layouts.app')

@section('content')

    @livewire('categories.price-ranges.index', ['category'=>$category])

@endsection

@section('css')

    @livewireStyles

@endsection

@section('js')

    @livewireScripts

@endsection