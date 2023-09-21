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
    <link href="/plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css" rel="stylesheet">

    <link href="/css/bootstrap.min.css" rel="stylesheet">

    <link href="/css/jquery.typeahead.min.css" rel="stylesheet">

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
        <div class="topbar-left hide-phone">
            <div class="text-center">
                <a href="javascript:void(0);" class="logo">
                    <i class="{{env('APP_LOGO_CLASS')}}"></i>
                </a>
            </div>
        </div>

        <nav class="navbar-custom">

            <ul class="list-inline float-right mb-0">

                <li class="list-inline-item dropdown notification-list">

                    <a class="nav-link dropdown-toggle waves-effect waves-light nav-user" data-toggle="dropdown" href="javascript:void(0);" role="button" aria-haspopup="false" aria-expanded="false">
                        <img src="/images/users/avatar-0.png" alt="user" class="rounded-circle hide-phone">
                        {{ Auth::user()->present()->first_name() }}
                    </a>

                    <div class="dropdown-menu dropdown-menu-right profile-dropdown " aria-labelledby="Preview">
                        <a href="{{ route('profile') }}" class="dropdown-item notify-item">
                            <i class="mdi mdi-account-star-variant"></i> <span>Profile</span>
                        </a>

                        <a href="{{ route('logout') }}" onclick="event.preventDefault();
                            document.getElementById('logout-form').submit();" class="dropdown-item notify-item">

                            <i class="mdi mdi-logout"></i> <span>Logout</span>
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            {{ csrf_field() }}
                        </form>

                    </div>
                </li>


            </ul>

            <ul class="list-inline menu-left mb-0">
                <li class="float-left">
                    <button class="button-menu-mobile open-left waves-light waves-effect">
                        <i class="mdi mdi-menu"></i>
                    </button>
                </li>
                <li class="float-left">
                    <div class="app-search">
                    <form role="search"id="search-form" class="" method="get" action="{{ route('search') }}">
                        <input type="text" name="q" placeholder="Search..." class="form-control">
                        <a href="javascript:void(0);" onclick="document.getElementById('search-form').submit();"><i class="fa fa-search"></i></a>
                    </form>
                    </div>
                </li>

            </ul>

        </nav>

    </div>

    @include('layouts.partials.nav')

    <div class="content-page">

        <div class="content">
            <div id="page-{{ Str::slug($title) }}" class="container">

                <!-- Page-Title -->
                <div class="row hidden-print">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">{{ $title }}</h4>
                            <ol class="breadcrumb float-right">
                            </ol>
                            <div class="clearfix"></div>
                        </div>
                    </div>
                </div>

                @include('flash::message')

                    <div class="alert alert-success" role="alert" style="display: none;">
                        <div class="success-body">
                        </div>
                    </div>

                    <div class="alert alert-danger" role="alert" style="display: {{ $errors->all()?"block":"none" }}">
                        <h5 class="alert-heading">Error</h5>
                        <div class="error-body">
                        @if($errors->all())
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{!! $error !!}</li>
                            @endforeach
                        </ul>
                        @endif
                        </div>
                    </div>


                    @if($warnings->all())
                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-warning" role="alert">
                                    <h5 class="alert-heading">Notifications</h5>
                                    <ul>
                                        @foreach($warnings->all() as $warning)
                                            <li>{!! $warning !!}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
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

<script src="/plugins/switchery/switchery.min.js"></script>
<script src="/plugins/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js"></script>

<script src="/js/plugins/wow.min.js"></script>

<script src="/js/plugins/tether.min.js"></script>
<script src="/js/plugins/bootstrap.min.js"></script>


<script src="/js/plugins/plugins.js"></script>
<script src="/js/plugins/fastclick.js"></script>


<!-- Custom main Js -->
<script src="/js/jquery.core.js"></script>
<script src="/js/jquery.app.js"></script>

@yield('js')


</body>
</html>