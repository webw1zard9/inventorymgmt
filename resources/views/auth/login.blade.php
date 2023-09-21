@extends('layouts.default')

@section('content')
    <form class="form-horizontal m-t-20" role="form" method="POST" action="{{ route('login') }}">
    {{ csrf_field() }}

        <div class="form-group row {{ $errors->has('email') ? ' has-error' : '' }}">
            <div class="col-12">
                <div class="input-group">
                    <span class="input-group-addon"><i class="mdi mdi-account"></i></span>
                    <input class="form-control" type="text" name="email" placeholder="Username" value="{{ old('email') }}" required autofocus autocomplete="off">

                </div>
                @if ($errors->has('email'))
                <ul class="parsley-errors-list filled pull-right">
                    <li class="parsley-required">{{ $errors->first('email') }}</li>
                </ul>
                @endif
            </div>
        </div>

        <div class="form-group row{{ $errors->has('password') ? ' has-error' : '' }}">
            <div class="col-12">
                <div class="input-group">
                    <span class="input-group-addon"><i class="mdi mdi-key"></i></span>
                    <input class="form-control" type="password" name="password" required="" autocomplete="off" placeholder="Password">
                </div>
                @if ($errors->has('password'))
                    <ul class="parsley-errors-list filled pull-right">
                        <li class="parsley-required">{{ $errors->first('password') }}</li>
                    </ul>
                @endif
            </div>
        </div>

        {{--<div class="form-group row">--}}
            {{--<div class="col-12">--}}
                {{--<div class="checkbox checkbox-primary">--}}
                    {{--<input id="checkbox-signup" type="checkbox">--}}
                    {{--<label for="checkbox-signup"{{ old('remember') ? 'checked' : '' }}>--}}
                        {{--Remember me--}}
                    {{--</label>--}}
                {{--</div>--}}

            {{--</div>--}}
        {{--</div>--}}

        <div class="form-group text-right m-t-20">
            <div class="col-xs-12">
                <button class="btn btn-primary btn-custom w-md waves-effect waves-light" type="submit">Log In
                </button>
            </div>
        </div>

        {{--<div class="form-group row m-t-30">--}}
            {{--<div class="col-sm-7">--}}
                {{--<a href="{{ route('password.request') }}" class="text-muted"><i class="fa fa-lock m-r-5"></i> Forgot your--}}
                    {{--password?</a>--}}
            {{--</div>--}}
            {{--<div class="col-sm-5 text-right">--}}
                {{--<a href="pages-register.html" class="text-muted">Create an account</a>--}}
            {{--</div>--}}
        {{--</div>--}}

    </form>
@endsection