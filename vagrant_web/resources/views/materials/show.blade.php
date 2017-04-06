@extends('layouts.app')

@section('content')

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

    @if(count($material->resources))
        <ul>
            @foreach($material->resources as $resource)
                <li>
                    {{$resource->public_path}} ({{ $resource->type }})
                </li>
            @endforeach
        </ul>
    @endif

    <p>{!! nl2br(e($material->description)) !!}</p>


@endsection