@extends('layouts.app')

@section('content')

    <div class="row">
        <div class="col-sm-10">
            <h1>{{$material->title}}</h1>

            <small>{{ $material->created_at->diffForHumans()}},
                by {{$material->creator->name}}
                @if($material->author !== NULL)
                    (Original von {{$material->author->title}})
                @endif
            </small>

            @if(count($material->bibleverses) || count ($material->keywords))
                <div>
                    @foreach($material->keywords as $keyword)
                        @include('keywords.linked', ['keyword' => $keyword])
                    @endforeach

                    @foreach($material->bibleverses as $bv)
                        @include('bibleverses.tag', ['bibleverse' => $bv])
                    @endforeach
                </div>
            @endif

            <p>{!! nl2br(e($material->description)) !!}</p>

            @if(count($material->resources)) {{-- Beginn of has Resources --}}
            <div class="row">

                @php
                    $resource = $material->resources->first();
                @endphp

                {{-- List all Resources if more then one --}}
                @if(count($material->resources) == 1 && $resource->getPreviewGenerator()->previewAble($resource) === TRUE)
                    {{-- Show Content of Resource, if only one is assigned to the material --}}
                    <div class="col-sm-12">

                        {!! $resource->getPreviewGenerator()->renderHTMLPreview($resource, 'material') !!}

                    </div>

                @else
                    @foreach($material->resources as $resource)
                        <div class="col-sm-6 col-md-4 col-lg-4 col-xl-3">
                            <div class="card ">
                                <img class="card-img-top img-"
                                     src="{{route('resource.image.preview', ['resource' => $resource->id, 'width' => 300])}}"
                                     alt="Resource Image"
                                     style="width:100%;">

                                <div class="card-block">
                                    <h4 class="card-title">{{ class_basename($resource) }}</h4>
                                    <p class="card-text">{{$resource->notes}}</p>
                                </div>

                                <div class="card-footer">
                                    <a href="#" class="btn btn-primary">download</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

            </div>
            @endif {{-- End of has Resources --}}


        </div>

        <div class="col-sm-2">
            <a href="{{ URL::route('pool.material.edit',[$material->id]) }}"
               class="btn btn-secondary">@lang('Bearbeiten')</a>
            <a href="#"
               class="btn btn-secondary">@lang('alles herunterladen')</a>
        </div>
    </div>


@endsection