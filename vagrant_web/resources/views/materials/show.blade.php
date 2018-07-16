@extends('layouts.app')

@section('content')

    @php

        $resources = [];

        /** @var \App\Models\Resource $resource */
        foreach($material->resources as $resource){
            $data = $resource->toArray();
            $prevGen = $resource->getPreviewGenerator();
            $data['previewable'] = [
                'image' => $prevGen->imagePreviewAble($resource),
                'html' => $prevGen->htmlPreviewAble($resource)
            ];
            $resources[] = $data;
        }

        $arMat = $material->toArray();
        $arMat['resources'] = $resources;

       // JavaScript::put(['material' => $arMat])
    @endphp

    <div id="app">
        <material-detail id="app" :material="{{ json_encode($arMat) }}"></material-detail>
    </div>


    <script src="http://localhost:8080/js/materialApp_build.js"></script>


@endsection