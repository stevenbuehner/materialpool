<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

@stack('styles')


<!-- CSRF Token -->
    {{--<meta name="csrf-token" content="{{ csrf_token() }}"> --}}

    <title>{{ config('app.name', 'Materialpool') }}</title>
    @include('layouts.partials.browser-icons')

    <!-- Scripts -->
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>


    @vite('resources/sass/main.scss')



    @stack('scripts')

</head>
<body>

<div id="mainContainer">
    <div class="container">
        @yield('content')
    </div>
</div>

</body>
</html>
