@if(!empty($resource->local_path) && file_exists(public_path($resource->local_path)))
    <img class="card-img-top img-fluid" src="{{ $resource->local_path }}" alt="Preview">
@elseif(!empty($resource->remote_path))
    <img class="card-img-top img-fluid" src="{{ $resource->remote_path }}" alt="Preview">
@endif
