@extends('layouts.app')

@push('scripts')
    <script src="/cards-addons.js"></script>
@endpush

@section('content')

    <h2>{{$title or ''}}</h2>

    <div class="card-columns">
        @foreach($materials as $m)
            <div class="card card-clickable" data-url="{{ URL::route('pool.material.show', $m->id) }}">

                @php
                    $resourceCount = count($m->resources);
                    $firstResource = $m->resources->first();
                @endphp

                @if($resourceCount > 0 && $firstResource->getPreviewGenerator()->previewAble($firstResource) === TRUE)
                    <img class="card-img-top "
                         src="{{route('resource.image.preview', ['resource' => $firstResource->id, 'width' => 300, 'height' => 300])}}"
                         alt="Resource Image"
                         style="width:100%;">
                @endif

                <div class="card-body">
                    <h6 class="card-title">{{ str_limit($m->title, 100) }}</h6>

                    <div class="clear-all"></div>

                    @if(count($m->keywords))
                        @foreach($m->keywords as $keyword)
                            @include('keywords.linked', ['keyword' => $keyword])
                        @endforeach
                    @endif

                    @if(count($m->bibleverses))
                        @foreach($m->bibleverses as $bv)
                            @include('bibleverses.tag', ['bibleverse' => $bv])
                        @endforeach
                    @endif

                    <div class="clear-all"></div>


                    @if($resourceCount > 0)
                        <small>von {{$m->creator->name}}, {{ $m->created_at->diffForHumans() }},
                            @php
                                $groupedResources = $m->resources->groupBy(function($resource){
                                         return class_basename($resource);
                                     });
                            @endphp

                            beinhaltet
                            @if($groupedResources->count() > 1)
                                @foreach($groupedResources as $key => $resourceGroup)
                                    <span class="text-nowrap">
                                        @choice("{0} :TYP|[1,*] :ANZAHLx :TYP", count($resourceGroup), ['TYP' => $key, 'ANZAHL' => count($resourceGroup)])
                                        ,
                                    </span>
                                @endforeach
                            @else
                                <span class="text-nowrap">
                                    @choice("{1}1 :TYP|[2,*] :ANZAHLx :TYP", $resourceCount, ['TYP' => class_basename($m->resources->first()), 'ANZAHL' => $resourceCount])
                                </span>
                            @endif
                        </small>
                    @else
                        <div class="alert alert-warning" role="alert">
                            <strong>@lang('Warnung')!</strong>
                            @lang("Dem Material sind keine Ressourcen zugeordnet.")
                        </div>
                    @endif


                    <div class="clear-all"></div>

                </div> <!-- end card-body-->

            </div>
        @endforeach
    </div>


    <script>
        $('.card-clickable').card();
    </script>

    {{ $materials->links('vendor.pagination.bootstrap-4') }}
@endsection