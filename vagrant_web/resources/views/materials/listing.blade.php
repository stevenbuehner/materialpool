@extends('layouts.app')

@section('content')

    <h2>{{$title or ''}}</h2>

    <div class="card-columns">
        @foreach($materials as $m)
            <div class="card">

                @php
                    $resourceCount = count($m->resources);
                @endphp

                @if($resourceCount > 1)
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

                    <small>von {{$m->creator->name}}, {{ $m->created_at->diffForHumans() }}</small>

                    <div class="clear-all"></div>

                    @if($resourceCount > 0)
                        <small>
                            @choice('{0} Das Material hat KEINE Ressourcen!|{1} Besteht aus :ANZAHL Resource:|[2,999] Besteht aus :ANZAHL Resourcen:|[1000,*] Enthält sehr viele Resourcen', $resourceCount, ['ANZAHL' => $resourceCount])
                        </small>
                    @else
                        <div class="alert alert-warning" role="alert">
                            <strong>@lang('Warnung')!</strong>
                            @lang("Dem Material sind keine Ressourcen zugeordnet.") </a>.
                        </div>
                    @endif





                    @if($resourceCount)
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
                           class="btn btn-primary">@lang('Öffnen')</a>
                    </div>
                </div>

            </div>
        @endforeach
    </div>

    {{ $materials->links('vendor.pagination.bootstrap-4') }}
@endsection