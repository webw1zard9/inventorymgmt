<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="">

    <title>{{ config('app.name') }}</title>

    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">

    <style>
        html,body { background: #fff; }

    </style>

</head>
<body>

<div class="wrapper-page">

    <div class="text-center m-b-50">
        <h1 class="display-1"><i class="{{env('APP_LOGO_CLASS')}} display-1"></i></h1>
    </div>

    @include('flash::message')

    @yield('content')

</div>

</body>
</html>