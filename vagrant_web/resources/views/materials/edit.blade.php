@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-2m-8">
            <h1>Material bearbeiten</h1>

            {!! Form::open(['route' => ['pool.material.update', $material->id], 'files' => FALSE, 'method' => 'PUT']) !!}


            <div class="form-group row">
                {!! Form::label('title', 'Titel', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-10">
                    {!! Form::text('title', $material->title, ['class' => 'form-control', 'required' => TRUE, 'placeholder' => 'title' ]) !!}
                    <div class="form-control-feedback">Success! You've done it.</div>
                    <small class="form-text text-muted">Example help text that remains unchanged.</small>
                </div>
            </div>


            <div class="form-group row">
                {!! Form::label('rating', 'Bewertung', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-10">
                    @include('parts.bar-rating.bar-rating', [
                        'rangeStart' => 0,
                        'rangeEnd' => 20,
                        'steps' => 1,
                        'name' => 'rating',
                        'value' => $material->rating
                    ])
                    <div class="form-control-feedback">Success! You've done it.</div>
                    <small class="form-text text-muted">Example help text that remains unchanged.</small>
                </div>
            </div>


            <div class="form-group row">
                {!! Form::label('description', 'Beschreibung', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-10">
                    {!! Form::textarea('description', $material->description, ['class' => 'form-control', 'required' => FALSE ]) !!}
                    <div class="form-control-feedback">Success! You've done it.</div>
                    <small class="form-text text-muted">Example help text that remains unchanged.</small>
                </div>
            </div>


            <div class="form-group row">
                <div class="col-sm-2">Autom. erstellt</div>
                <div class="col-sm-10 col-sm-offset-2">
                    {!! Form::checkbox('from_bot', 1, $material->from_bot, ['class' => 'form-check-label' ]) !!}
                    <small class="form-text text-muted">Nach manueller Nacharbeit kommt der Haken bitte raus</small>
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('keywords', 'Schlagwörter', ['class' => 'col-sm-2 col-form-label']) !!}

                <div class="col-sm-10">
                    @include('parts.select2.multi-ajax', [
                        'url' => route('api.v1.keywords.index'),
                        'displayField' => 'title',
                        'selected' => $material->keywords,
                        'name' => 'keywords' ,
                        'placeholder' => 'Schlagwörter eingeben',
                        'updateRelevanceUrl' => '/api/v1/material/' . $material->id .'/keyword/',
                        'deleteAssignmentUrl' => '/api/v1/material/' . $material->id .'/keyword/',
                        'createKeywordUrl' => route('api.v1.keywords.create'),
                    ])

                    <small class="form-text text-muted">Schlüsselwörter und deren Relevanz werden direkt gespeichert.
                    </small>
                </div>
            </div>

            <div class="form-group row">
                {!! Form::label('bibleverses', 'Bibelverse', ['class' => 'col-sm-2 col-form-label']) !!}

                <div class="col-sm-10">
                    @include('parts.select2.multi-ajax', [
                        'url' => route('api.v1.bibleverses.index'),
                        'displayField' => 'label',
                        'selected' => $material->bibleverses->sortBy('from'),
                        'name' => 'bibleverses' ,
                        'placeholder' => 'Bibelverse eingeben',
                        'updateRelevanceUrl' => '/api/v1/material/' . $material->id . '/bibleverse/',
                        'deleteAssignmentUrl' => '/api/v1/material/' . $material->id . '/bibleverse/',
                        'createKeywordUrl' => route('api.v1.bibleverses.store'),
                    ])

                    <small class="form-text text-muted">Bibelstellen und deren Relevanz werden direkt gespeichert.
                    </small>
                </div>
            </div>


            <div class="form-group row">
                <div class="offset-sm-2 col-sm-10">
                    {!! Form::submit('speichern', ['class' => 'btn btn-primary']) !!}
                </div>
            </div>


            {!! Form::close() !!}

            @include('layouts.errors')

        </div>


        @if($material->resources->count())
            <h5>zugeordnete Resourcen</h5>

            <div class="card-columns">
                @foreach($material->resources as $r)
                    @include('resources.resource-mini-card-with-limitation',
                    ['resource' => $r,
                    'limitation' => $r->pivot->limitation])
                @endforeach
            </div>


            <a class="btn btn-secondary pull-right btn-sm" role="button" href="#">weitere Resource zuordnen</a>
        @else
            <div class="alert alert-warning">
                Keine Ressourcen zugeordnet

                <a class="btn btn-sm btn-secondary" role="button" href="#">Resource zuordnen</a>
            </div>
        @endif


    </div>

@endsection