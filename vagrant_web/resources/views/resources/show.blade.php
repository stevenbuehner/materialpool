@extends('layouts.app')

@section('content')
    <div class="jumbotron">
        <h1 class="display-5">
            @include('resources.partials.filename-or-classname')
        </h1>

        <hr class="my-4">

        <p class="lead">
            <a class="btn btn-primary btn-lg" href="{{ route('pool.resource.edit', $resource->id) }}"
               role="button">Bearbeiten</a>

            @if($resource instanceof \App\Models\PdfFile)
                <a class="btn btn-primary btn-lg" href="{{ route('pool.resource.assign.pdf.material', $resource->id) }}"
                   role="button">Material zuordnen</a>
            @endif
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