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

        JavaScript::put(['material' => $arMat])
    @endphp

    <div id="app">


        <div class="row" style="margin-bottom: 1em">
            <a href="{{ URL::route('pool.material.edit',[$material->id]) }}"
               class="btn btn-primary"
               title="@lang('pool.edit-material')"
            >@lang('pool.edit')</a>

            <a href="{{ URL::route('pool.material.delete',[$material->id]) }}"
               class="btn btn-danger"
               title="@lang('pool.delete')"
            >@lang('pool.delete')</a>
        </div>

        @if(count($material->resources)) {{-- Beginn of has Resources --}}
        <div class="row" style="margin-bottom: 1em">

            @php
                $resource = $material->resources->first();
            @endphp

            {{-- List all Resources if more then one --}}
            @if(count($material->resources) == 1 && ResourcePreview::htmlPossible($resource, 'large') === TRUE)
                {{-- Show Content of Resource, if only one is assigned to the material --}}
                <div class="col-sm-12 rounded" style="border: solid 1px; padding: 1em">

                    {!! ResourcePreview::html($resource, $resource->pivot->limitation, 'material', 'large') !!}

                </div>

            @else
                @foreach($material->resources as $resource)
                    <div class="col-sm-6 col-md-4 col-lg-4 col-xl-3">
                        <div class="card card-clickable"
                             data-url="{{ URL::route('pool.resource.show', $resource->id) }}">

                            <div class="card-body">

                                @if(ResourcePreview::imagePossible($resource, 'thumb') === TRUE)
                                    <img class="card-img-top "
                                         src="{{route('resource.image.preview', ['resource' => $resource->id, 'width' => 300, 'height' => 300])}}"
                                         alt="Resource Image"
                                         style="width:100%;"/>
                                @elseif(ResourcePreview::htmlPossible($resource, 'thumb') === TRUE)
                                    {!! ResourcePreview::html($resource, $resource->pivot->limitation, 'material', 'thumb') !!}
                                @else
                                    No Preview
                                @endif

                            </div>

                            <div class="card-footer">
                                <a href="{{ URL::route('pool.resource.download', [$resource->id]) }}"
                                   class="btn btn-sm btn-secondary">@lang('pool.download-file')</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

        </div>
        @endif {{-- End of has Resources --}}


    </div>

    <script>
        // var materialApp = new vu
    </script>

    @if($andereMaterialien->count() > 0)
        <div class="alert alert-warning" role="alert">
            <strong>@lang('pool.attention'):</strong>
            {{trans_choice('pool.material.other-assigned-material.pl', $andereMaterialien->count(), ['count' => $andereMaterialien->count()])}}
        </div>
    @endif

    <script src="http://localhost:8080/js/materialApp_build.js"></script>

    <script>
        // $('.card-clickable').card();
    </script>

@endsection