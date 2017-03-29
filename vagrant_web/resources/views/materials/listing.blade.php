@extends('layouts.app')

@section('content')

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
                    <a href="{{ URL::route('pool.material.index') }}/{{$m->id}}"
                       class="btn btn-primary">@lang('Open material')</a>
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

                @if(count($m->bibleverses))
                    <div class="card-block">
                        @foreach($m->bibleverses as $bv)
                            @include('bibleverses.tag', ['bibleverse' => $bv])
                        @endforeach
                    </div>
                @endif


            </div>
        @endforeach
    </div>

    {{ $materials->links('vendor.pagination.bootstrap-4') }}
@endsection