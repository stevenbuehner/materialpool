@extends('layouts.app')

@section('content')
    <h1>Lade eine neue Resource hoch</h1>

    {!! Form::open(['route' => 'pool.resource.store', 'files' => TRUE]) !!}


    <div class="form-group">
        {!! Form::label('file', 'Datei') !!}
        {!! Form::file('file', ['class' => 'form-control', 'placeholder' => "Datei hochladen", 'required' => TRUE ]) !!}
    </div>

    <div class="form-group">
        {!! Form::submit('hochladen', ['class' => 'btn btn-primary']) !!}
    </div>


    {!! Form::close() !!}

    @include('layouts.errors')



@endsection