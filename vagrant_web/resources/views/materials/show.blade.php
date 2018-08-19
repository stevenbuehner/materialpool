@extends('layouts.app')

@section('content')

    @php

        $mph = new \App\Http\View\Helpers\MaterialPreviewHelper();
        $arMat = $mph->materialToArray($material, []);

        //         JavaScript::put(['material' => $arMat])
    @endphp

    <div id="app">
        <material-detail id="app" :material="{{ json_encode($arMat) }}"></material-detail>
    </div>


    <script src="http://localhost:8080/js/materialApp_build.js"></script>


@endsection