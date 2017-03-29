@extends('layouts.app')

@section('content')
    <div class="jumbotron">
        <h1 class="display-3">Resource</h1>
        <p class="lead">
            {{$resource->type}}
        </p>

        <hr class="my-4">

        <p class="lead">
            <a class="btn btn-primary btn-lg" href="{{ route('pool.resource.edit', $resource->id) }}"
               role="button">Bearbeiten</a>
        </p>


        @if( count($resource->materials) )
            <hr class="my-4">

            <p class="lead">
                @foreach($resource->materials as $material)
                    @include('materials.material-and-keywords-partial', ['material' => $material])
                @endforeach
            </p>
        @endif

    </div>

    <div class=""></div>





@endsection