@extends('layouts.app')

@section('content')

    @php

        $mph = new \App\Http\View\Helpers\MaterialPreviewHelper();
        $myMaterials = [];

        foreach($materials as $mat){
            $myMaterials[] = $mph->materialToArray($mat, ['previewable']);
        }


        //         JavaScript::put(['material' => $arMat])
    @endphp

    <h2>Alle Materialien</h2>
    <div id="app">
        <material-card-listing :materials="{{ json_encode($myMaterials) }}"></material-card-listing>
    </div>


    <script src="http://localhost:8080/js/materialApp_build.js"></script>

@endsection