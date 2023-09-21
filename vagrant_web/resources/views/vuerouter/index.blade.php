<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>
    @stack('scripts')

</head>
<body>

<div id="app">
</div>


@php

    if(isset($store) && is_array($store)){
	    echo '<script>' . JavaScript::constructJavaScript(['store' => $store]) .'</script>' . "\n";
    }

    if(isset($route) ){
        echo '<script>' . JavaScript::constructJavaScript(['route' => $route]).'</script>' . "\n";
    }

@endphp

@if (env('APP_ENV') =='production')
    <script src="/js/main_build.js"></script>
    <!--<script src="/js/vendor.bundle.js"></script>-->
    <link rel="stylesheet" type="text/css" href="/css/main.css">
@else
    <script src="http://192.168.3.28:8080/js/main_build.js"></script>
    <!--<script src="http://localhost:8080/js/vendor.bundle.js"></script>-->
@endif

</body>
</html>
