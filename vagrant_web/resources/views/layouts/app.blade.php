<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <!-- Latest compiled and minified CSS -->
   <!-- <link rel="stylesheet" href="/css/dependencies.css">
    <link rel="stylesheet" href="/css/app.css"> -->
    <link rel="stylesheet" href="/css/develop.css">
@stack('styles')


<!-- CSRF Token -->
    {{--<meta name="csrf-token" content="{{ csrf_token() }}"> --}}

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>
    <script src="http://localhost:8080/js/dependencies_build.js"></script>
    <script src="http://localhost:8080/js/app_build.js"></script>
    @stack('scripts')

</head>
<body>
<div id="mainContainer">

    @include('navbar.main.nav')

    <div class="container">
        @yield('content')
    </div>
</div>

</body>
</html>
