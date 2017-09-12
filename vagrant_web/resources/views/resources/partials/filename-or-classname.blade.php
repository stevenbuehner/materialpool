@if($resource instanceof \App\Models\File && !empty($resource->original_filename))
    {{$resource->original_filename}}
@else
    {{class_basename($resource)}}
@endif