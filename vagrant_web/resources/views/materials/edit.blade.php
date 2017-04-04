@extends('layouts.app')

@section('content')
    <h1>Material bearbeiten</h1>

    {!! Form::open(['route' => ['pool.material.update', $material->id], 'files' => FALSE, 'method' => 'PUT']) !!}


    <div class="form-group">
        {!! Form::label('title', 'Titel') !!}
        {!! Form::text('title', $material->title, ['class' => 'form-control', 'required' => TRUE ]) !!}
    </div>

    <div class="form-group">
        {!! Form::label('description', 'Beschreibung') !!}
        {!! Form::textarea('description', $material->description, ['class' => 'form-control', 'required' => FALSE ]) !!}
    </div>


    <div class="form-group">
        {!! Form::label('rating', 'Bewertung') !!}
        {!! Form::number('rating', $material->rating, ['class' => 'form-control']) !!}
    </div>

    <div class="form-group">
        {!! Form::label('from_bot', 'Ausschließlich computergeneriert') !!}
        {!! Form::checkbox('from_bot', 1, $material->from_bot, ['class' => 'form-control' ]) !!}
    </div>

    <div class="form-group">
        {!! Form::label('keywords', 'Schlagwörter') !!}

        @include('parts.select2.multi-ajax', [
            'url' => route('api.v1.keywords.index'),
            'displayField' => 'title',
            'selected' => $material->keywords,
            'name' => 'keywords' ,
            'placeholder' => 'Schlagwörter eingeben',
            'updateRelevanceUrl' => '/api/v1/material/' . $material->id .'/keyword/',
            'createAssignmentUrl' => '/api/v1/material/' . $material->id .'/keyword/',
            'deleteAssignmentUrl' => '/api/v1/material/' . $material->id .'/keyword/',
        ])
    </div>

    <div class="form-group">
        {!! Form::label('bibleverses', 'Bibelverse') !!}

        @include('parts.select2.multi-ajax', [
            'url' => route('api.v1.bibleverses.guess'),
            'displayField' => 'title',
            'selected' => $material->bibleverses,
            'name' => 'bibleverses' ,
            'placeholder' => 'Bibelverse eingeben',
            'updateRelevanceUrl' => '',
            'createAssignmentUrl' => '',
            'createKeywordsUrl' => '',
            'deleteAssignmentUrl' => '',

        ])
    </div>


    <div class="form-group">
        {!! Form::submit('speichern', ['class' => 'btn btn-primary']) !!}
    </div>


    {!! Form::close() !!}

    @include('layouts.errors')


    @if($material->resources->count())
        <h3>Ressourcen</h3>
        <ul>
            @foreach($material->resources as $r)
                <li>
                    {{$r->type}} ({{$r->id}})
                </li>
            @endforeach
        </ul>
    @endif


@endsection