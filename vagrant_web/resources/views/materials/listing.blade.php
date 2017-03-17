@extends('layouts.app')

@section('content')

    <p>@lang('Keywords included in search:')
        @foreach($keywords as $k)
            @include('keywords.linked', ['keyword' => $k])
        @endforeach
    </p>

    <hr>

    <div class="card-columns">
        @foreach($materials as $m)
            <div class="card">
                {!! ResourceHelper::firstResourcePreviewHtml( $m->resources ) !!}

                @if(count($m->resources) > 1)
                    <span class="badge badge-pill badge-default">{{count($m->resources)}}</span>
                @endif
                <div class="card-block">
                    <h3 class="card-title">{{ $m->title }}</h3>
                    <p class="card-text">{{ $m->description }}</p>
                    <a href="#" class="btn btn-primary">@lang('Open material')</a>
                    <div>
                        <small>{{ $m->created_at->diffForHumans() }}</small>
                    </div>

                </div>

                @if(count($m->keywords))
                    <div class="card-block">
                        @foreach($m->keywords as $keyword)
                            @include('keywords.linked', ['keyword' => $keyword])
                        @endforeach
                    </div>
                @endif


            </div>
        @endforeach
    </div>

    {{ $materials->links('vendor.pagination.bootstrap-4') }}
@endsection