@extends('layouts.app')

@section('content')

    <h1>{{$material->title}}</h1>
    <small>{{ $material->created_at->diffForHumans() }}</small>


    @if(count($material->resources))
        <ul>
            @foreach($material->resources as $resource)
                <li>
                    {{$resource->public_path}} ({{ $resource->type }})
                </li>
            @endforeach
        </ul>
    @endif

    <p>{{ $material->description }}</p>

    @if(count($material->keywords))
        <div class="card-block">
            @foreach($material->keywords as $keyword)
                @include('keywords.linked', ['keyword' => $keyword])
            @endforeach
        </div>
    @endif

    @if(count($material->bibleverses))
        <div class="card-block">
            @foreach($material->bibleverses as $bv)
                @include('bibleverses.tag', ['bibleverse' => $bv])
            @endforeach
        </div>
    @endif

@endsection