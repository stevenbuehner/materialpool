@extends('layouts.app')

@push('scripts')
    <script src="/cards-addons.js"></script>
@endpush

@section('content')

    <h1>{{$material->title}}</h1>

    <small>
        @lang('pool.Contains')
        {{trans_choice('pool.resource.count', count($material->resources), ['count' => count($material->resources)])}},
        @lang('pool.eddited') {{ $material->created_at->diffForHumans()}},
        @lang('pool.by') {{$material->creator->name}}
        @if($material->author !== NULL)
            (@lang('pool.resource.author-is', ['name' => $material->author->title]))
        @endif
    </small>

    <div class="row" id="keywords_app">
        @if(count($material->bibleverses) || count ($material->keywords))
            <div>
                @foreach($material->keywords as $keyword)
                    @include('keywords.linked', ['keyword' => $keyword])
                @endforeach

                @foreach($material->bibleverses->sortBy('from') as $bv)
                    @include('bibleverses.tag', ['bibleverse' => $bv])
                @endforeach
            </div>
        @endif

        <p>{!! nl2br(e($material->description)) !!}</p>
    </div>


    <script>
        var keywordVue = new Vue({
            el: '#keywords_app',
            created: function () {
            },

            mounted: function () {
            }
        });

    </script>

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
                    <div class="card card-clickable" data-url="{{ URL::route('pool.resource.show', $resource->id) }}">

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


    @if($andereMaterialien->count() > 0)
        <div class="alert alert-warning" role="alert">
            <strong>@lang('pool.attention'):</strong>
            {{trans_choice('pool.material.other-assigned-material.pl', $andereMaterialien->count(), ['count' => $andereMaterialien->count()])}}
        </div>

    @endif

    <script>
        $('.card-clickable').card();
    </script>

@endsection