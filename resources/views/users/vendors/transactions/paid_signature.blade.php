@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-lg-12">

            <div class="row">
                <div class="col-lg-12 mb-3">
                    <a class="btn btn-primary" href="{{ url()->previous() }}">Back</a>
                </div>
            </div>

            <div class="card-box">

                <h4>Vendor: {{ $orderTransaction->vendor->name }}</h4>
                <h4>Payment Method: {{ $orderTransaction->payment_method }}</h4>
                <h4>Amount: {{ display_currency($orderTransaction->amount) }}</h4>
                <hr>

                    @if($orderTransaction->signature)

                        <h4>Picked up by:</h4>
                        <h5>Name: {{ $orderTransaction->signature->name }}</h5>

                        <img src="{{ $orderTransaction->signature->signature_png }}">
                        <h5>{{ $orderTransaction->signature->created_at->format(config('inventorymgmt.date_time_format')) }}</h5>
                        <h5>User: {{ $orderTransaction->signature->user->name }}</h5>

                    @else

                        {{ Form::open(['id'=>'signature-form', 'class'=>'form-horizontal col-xl-4 col-lg-6', 'url'=>route('vendors.transactions.paid-signature.store', [$vendor, $orderTransaction])]) }}

                        {{ Form::hidden('signature_image', null, ['id'=>'signature_image']) }}
                        <h4>Received by:</h4>
                        <div class="form-group">
                            <label for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" required="" placeholder="Name" class="form-control" id="name">
                        </div>

                        <div class="form-group sigPad">

                            <label for="">Signature <span class="text-danger">*</span></label>

                            <div class="sig sigWrapper">
                                <canvas class="pad" width="520" height="100"></canvas>
                                <input type="hidden" name="signature" class="output">
                            </div>
                            <p class="clearButton pull-right"><a href="#clear">Clear</a></p>

                        </div>

                        <div class="clearfix"></div>

                        <div class="form-group m-b-0">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">Save</button>
                        </div>

                        {{ Form::close() }}

                    @endif

            </div>
        </div>
    </div>



@endsection

@section('css')

    <link href="{{ asset('css/jquery.signaturepad.css') }}" rel="stylesheet">

@endsection

@section('js')

    <script src="{{ asset('js/json2.min.js') }}"></script>
    <script src="{{ asset('js/jquery.signaturepad.min.js') }}"></script>

    <script>

        $(function(){
             var instance = $('.sigPad').signaturePad({
                 drawOnly:true,
                 drawBezierCurves:true,
                 lineTop:200,
             });


             $('#signature-form').submit(function() {

                 $('#signature_image').val(instance.getSignatureImage());
                 return true;
             });

        });

    </script>


@endsection