@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-sm-8">
            <h1>Resource bearbeiten</h1>

            {!! Form::open(['route' => ['pool.resource.update', $resource->id], 'files' => FALSE, 'method' => 'PUT']) !!}

            <div class="form-group row">
                {!! Form::label('type', 'Typ', ['class' => 'col-sm-2 col-form-label']) !!}
                <div class="col-sm-10">
                    {!! Form::select('type', \App\Models\Resource::getSingleTableTypeMap(), $resource->type, ['class' => 'form-control', 'required' => TRUE ]) !!}
                    <div class="form-control-feedback">Success! You've done it.</div>
                    <small class="form-text text-muted">Example help text that remains unchanged.</small>
                </div>
            </div>

            @foreach($resource->getAdditionalEditViews() as $viewName )
                @include($viewName)
            @endforeach


            <div class="form-group row">
                <div class="offset-sm-2 col-sm-10">
                    {!! Form::submit('speichern', ['class' => 'btn btn-primary']) !!}
                </div>
            </div>


            {!! Form::close() !!}

            @include('layouts.errors')
        </div>

        <div class="col-sm-4">
            @if($resource->materials->count())
                <h5>zugeordnete Materialien</h5>

                <div class="card-columns>">
                    @foreach($resource->materials as $material)

                        @include('materials.material-mini-card-partial', ['material' => $material])
                    @endforeach
                </div>

                <div class="card-block">
                    <a class="btn btn-secondary pull-right btn-sm" role="button">weiteres Material erstellen</a>
                </div>
            @else
                <div class="alert alert-warning">
                    Kein Material zugeordnet

                    <a class="btn btn-sm btn-secondary" role="button" href="#">Material erstellen</a>
                </div>
            @endif

        </div>


    </div>
@endsection