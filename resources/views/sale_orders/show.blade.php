@extends('layouts.app')

@section('content')

    @livewire('sale-order.show', ['saleOrder'=>$saleOrder])

@endsection

@section('css')

    @livewireStyles

@endsection

@section('js')

    @livewireScripts

@endsection
