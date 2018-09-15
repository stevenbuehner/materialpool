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


<script src="http://localhost:8080/js/searchApp_build.js"></script>

</body>
</html>
