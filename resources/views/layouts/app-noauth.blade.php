<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <link href="/plugins/switchery/switchery.min.css" rel="stylesheet" />
    <link href="/plugins/morris/morris.css" rel="stylesheet">

    <link href="css/bootstrap.min.css" rel="stylesheet">

    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    <script src="/js/plugins/modernizr.min.js"></script>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    @yield('css')

</head>


<body class="fixed-left">

<div id="app">

    <!-- Begin page -->
    <div id="wrapper">

    <!-- Top Bar Start -->
    <div class="topbar">

        <!-- LOGO -->
        <div class="topbar-left">
            <div class="text-center">
                <a href="{{ route('dashboard') }}" class="logo">
                    <i class="{{env('APP_LOGO_CLASS')}}"></i>
                </a>
            </div>
        </div>

        <!-- Button mobile view to collapse sidebar menu -->
        <nav class="navbar-custom">

        </nav>

    </div>
    <div class="content-page" style="margin-left:0">

        <div class="content">
            <div id="page-{{ Str::slug($title) }}" class="container">

                <!-- Page-Title -->
                <div class="row hidden-print">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">{{ $title }}</h4>
                            <ol class="breadcrumb float-right">
                                {{--<li class="breadcrumb-item"><a href="{{ URL::previous() }}">&laquo; Back</a></li>--}}
                                {{--<li class="breadcrumb-item active">Back</li>--}}
                            </ol>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>

                @include('flash::message')

                @if($errors->all())
                <div class="alert alert-danger" role="alert">
                    <h5 class="alert-heading">Error</h5>
                    {{ Html::ul($errors->all()) }}
                </div>
                @endif

                @if($warnings->all())
                    <div class="alert alert-warning" role="alert">
                        <h5 class="alert-heading">Notice</h5>
                        {{ Html::ul($warnings->all()) }}
                    </div>
                @endif

                <!-- Start content -->
                @yield('content')
                <!-- end content -->

            </div>
        </div>


        @include('layouts.partials.footer')

    </div>

</div>

</div>


<script>
    var resizefunc = [];
</script>

<script src="{{ mix('js/app.js') }}"></script>

<script src="plugins/switchery/switchery.min.js"></script>
<script src="js/plugins/wow.min.js"></script>

<script src="js/plugins/tether.min.js"></script>
<script src="js/plugins/bootstrap.min.js"></script>


<script src="js/plugins/plugins.js"></script>
<script src="js/plugins/fastclick.js"></script>


<!-- Custom main Js -->
<script src="js/jquery.core.js"></script>
<script src="js/jquery.app.js"></script>

@yield('js')

</body>
</html>