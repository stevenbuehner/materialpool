@extends('layouts.app')

@section('content')
    <h1>Erstelle eine neue Resource</h1>

    <div class="card-columns">

        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Dateibasierte Resource</h3>
                <p class="card-text">Bilder, Dokumente, Filme, ...</p>

                {!! Form::open(['route' => 'pool.resource.store', 'files' => TRUE]) !!}

                <div class="form-group">
                    {!! Form::label('file[]', 'Datei') !!}
                    {!! Form::file('file[]', ['class' => 'form-control', 'placeholder' => "Datei hochladen", 'required' => TRUE, 'multiple' => TRUE, 'id' =>'files' ]) !!}


                    <script type="text/javascript">
                        @php
                            $maxFileSize = (int) ini_get('max_file_uploads');
                        @endphp
                        $("#files").on("change", function () {
                            if ($("#files")[0].files.length > {{$maxFileSize}}) {
                                alert("You can select only up to {{$maxFileSize}} files");
                                $("#files").val('');
                            }
                        });
                    </script>
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


    </div>

    @include('layouts.errors')

@endsection