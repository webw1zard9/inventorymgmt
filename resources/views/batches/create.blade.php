@extends('layouts.app')

@section('content')

    <div class="row">

        <div class="col-lg-12">

            <h4 class="m-t-0 header-title"><b>Create Product</b></h4>

            {{ Form::open(['id'=>'add-batch-item', 'class'=>'form-horizontal', 'url'=>route('batches.store')]) }}

                <div class="card-box add_batch_row">

                    @include('_partials/_add_batch_item', ['show_non_inventory_item'=>true, 'location_id'=>null])

                </div>

            <button type="submit" class="btn btn-primary waves-effect waves-light add_batch_submit">Add Item</button>

            {{ Form::close() }}

        </div>

    </div>

@endsection

@section('js')



@endsection