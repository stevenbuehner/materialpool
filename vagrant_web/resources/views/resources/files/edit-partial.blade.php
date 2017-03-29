<div class="form-group disabled">
    {!! Form::label('remote_path', 'Externer Link') !!}
    {!! Form::text('remote_path', $resource->remote_path, ['class' => 'form-control', 'placeholder' => "http:// ..." ]) !!}

    @if(!empty($resource->remote_path))
        <div class="old-value">({{$resource->remote_path}})</div>
    @endif
</div>

<div class="form-group">
    {!! Form::label('local_path', 'Interner Dateipfad') !!}
    <div class="form-">{{$resource->local_path}}</div>
</div>

<div class="form-group">
    {!! Form::label('original_filename', 'Dateiname') !!}
    {!! Form::text('original_filename', $resource->original_filename, ['class' => 'form-control', 'placeholder' => "Dateiname", 'required' => TRUE]) !!}

    @if(!empty($resource->original_filename))
        <div class="old-value">({{$resource->original_filename}})</div>
    @endif
</div>

