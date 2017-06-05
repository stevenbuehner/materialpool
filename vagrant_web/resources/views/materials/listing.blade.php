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
                    <h3 class="card-title">{{ str_limit($m->title, 100) }}</h3>

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

                    <small>{{ $m->created_at->diffForHumans() }}, by {{$m->creator->name}}</small>

                    <div class="clear-all"></div>

                    <small>
                        @choice('{0} Das Material hat KEINE Ressourcen!|{1} Besteht aus :ANZAHL Resource:|[2,999] Besteht aus :ANZAHL Resourcen:|[1000,*] Enthält sehr viele Resourcen', count($m->resources), ['ANZAHL' => count($m->resources)])
                    </small>
                    @if(count($m->resources))
                        <ul>
                            @foreach($m->resources->groupBy(function($resource){
                                return class_basename($resource);
                            }) as $key => $resourceGroup)
                                <li class="small">@choice("{0} :TYP|[1,*] :ANZAHLx :TYP", count($resourceGroup), ['TYP' => $key, 'ANZAHL' => count($resourceGroup)])</li>
                            @endforeach
                        </ul>

                    @endif

                    <div class="clear-all"></div>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Material">
                        <a href="{{ URL::route('pool.material.show', [$m->id]) }}"
                           class="btn btn-primary">@lang('mehr')</a>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

    {{ $materials->links('vendor.pagination.bootstrap-4') }}
@endsection