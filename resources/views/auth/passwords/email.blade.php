@extends('layouts.default')

@section('content')

    {{--<form class="form-horizontal" role="form" method="POST" action="{{ route('password.email') }}">--}}

    <form method="post" action="{{ route('password.email') }}" role="form" class="text-center m-t-20">

        {{ csrf_field() }}

        @if (session('status'))

            <div class="alert alert-success alert-dismissable">

                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                Enter your <b>Email</b> and instructions will be sent to you!

                <div class="alert alert-success">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <div class="form-group m-b-0{{ $errors->has('email') ? ' has-error' : '' }}">
            <div class="input-group">

                <input type="email" class="form-control" name="email" placeholder="Enter Email" value="{{ old('email') }}" required>

                <span class="input-group-btn"> <button type="submit" class="btn btn-email btn-primary waves-effect waves-light">Send</button> </span>
            </div>

            @if ($errors->has('email'))
                <ul class="parsley-errors-list filled pull-right">
                    <li class="parsley-required">{{ $errors->first('email') }}</li>
                </ul>
            @endif

        </div>

    </form>

{{--<div class="container">--}}
    {{--<div class="row">--}}
        {{--<div class="col-md-8 col-md-offset-2">--}}
            {{--<div class="panel panel-default">--}}
                {{--<div class="panel-heading">Reset Password</div>--}}
                {{--<div class="panel-body">--}}
                    {{--@if (session('status'))--}}
                        {{--<div class="alert alert-success">--}}
                            {{--{{ session('status') }}--}}
                        {{--</div>--}}
                    {{--@endif--}}

                    {{--<form class="form-horizontal" role="form" method="POST" action="{{ route('password.email') }}">--}}
                        {{--{{ csrf_field() }}--}}

                        {{--<div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">--}}
                            {{--<label for="email" class="col-md-4 control-label">E-Mail Address</label>--}}

                            {{--<div class="col-md-6">--}}
                                {{--<input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>--}}

                                {{--@if ($errors->has('email'))--}}
                                    {{--<span class="help-block">--}}
                                        {{--<strong>{{ $errors->first('email') }}</strong>--}}
                                    {{--</span>--}}
                                {{--@endif--}}
                            {{--</div>--}}
                        {{--</div>--}}

                        {{--<div class="form-group">--}}
                            {{--<div class="col-md-6 col-md-offset-4">--}}
                                {{--<button type="submit" class="btn btn-primary">--}}
                                    {{--Send Password Reset Link--}}
                                {{--</button>--}}
                            {{--</div>--}}
                        {{--</div>--}}
                    {{--</form>--}}
                {{--</div>--}}
            {{--</div>--}}
        {{--</div>--}}
    {{--</div>--}}
{{--</div>--}}
@endsection
