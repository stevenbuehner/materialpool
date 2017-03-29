@extends('layouts.app')

@section('content')
    <h1>Resource bearbeiten</h1>

    {!! Form::open(['route' => ['pool.resource.update', $resource->id], 'files' => FALSE, 'method' => 'PUT']) !!}

    <div class="form-group">
        {!! Form::label('type', 'Dateityp') !!}
        {!! Form::select('type', \App\Models\Keyword::getSingleTableTypeMap(), $resource->type, ['class' => 'form-control', 'required' => FALSE ]) !!}
    </div>


    @foreach($resource->getAdditionalEditViews() as $viewName )
        @include($viewName)
    @endforeach


    <div class="form-group">
        {!! Form::submit('speichern', ['class' => 'btn btn-primary']) !!}
    </div>


    {!! Form::close() !!}

    @include('layouts.errors')


@endsection