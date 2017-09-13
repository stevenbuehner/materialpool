@component('resources.generators.component')
    @slot('title')
        {{ $title or class_basename($resource) }}
    @endslot

    @slot('menu')
        <a href="{{ URL::route('pool.resource.download', [$resource->id]) }}"
           class="btn btn-secondary">download</a>
    @endslot


    <img style="max-width: {{config('app.resource.preview.maxWidth')}}px; max-height: {{config('app.resource.preview.maxHeight')}}px;"
         class="card-img-top img-fluid"
         src="{{ $src }}"
         alt="Preview">

@endcomponent
