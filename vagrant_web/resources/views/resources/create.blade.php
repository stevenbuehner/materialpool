@extends('layouts.app')

@section('content')
    <h1>Erstelle eine neue Resource</h1>

    <div class="card-columns">

        <div class="card">
            <div class="card-block">
                <h3 class="card-title">Dateibasierte Resource</h3>
                <p class="card-text">Bilder, Dokumente, Filme, ...</p>

                {!! Form::open(['route' => 'pool.resource.store.file', 'files' => TRUE]) !!}

                <div class="form-group">
                    {!! Form::label('file', 'Datei') !!}
                    {!! Form::file('file', ['class' => 'form-control', 'placeholder' => "Datei hochladen", 'required' => TRUE ]) !!}
                </div>

                <div class="form-group">
                    {!! Form::label('meta', 'Metainformationen') !!}
                    {!! Form::textarea('meta', '', ['class' => 'form-control', 'placeholder' => "Metadaten eingeben"]) !!}
                </div>

                <div class="form-group">
                    {!! Form::submit('hochladen', ['class' => 'btn btn-primary']) !!}
                </div>

                {!! Form::close() !!}
            </div>
        </div>


        <div class="card">
            <div class="card-block">
                <h3 class="card-title">Textdatei importieren</h3>
                <p class="card-text">.txt Datei oder freier Inhalt</p>

                {!! Form::open(['route' => 'pool.resource.store.text', 'files' => TRUE]) !!}

                <div class="form-group">
                    {!! Form::label('file', 'Textdatei') !!}
                    {!! Form::file('file', ['class' => 'form-control', 'placeholder' => "Textdatei hochladen", 'accept' => 'text/*']) !!}
                </div>


                <div class="form-group">
                    {!! Form::label('content', 'oder nur den Text') !!}
                    {!! Form::textarea('content', '', ['class' => 'form-control', 'placeholder' => "Textinhalt eingeben"]) !!}
                </div>

                <div class="form-group">
                    {!! Form::submit('hochladen', ['class' => 'btn btn-primary']) !!}
                </div>


                {!! Form::close() !!}
            </div>
        </div>


    </div>

    @include('layouts.errors')

@endsection