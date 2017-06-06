@component('resources.generators.component')
    @slot('title')
        {{$title or $resource->notes or class_basename($resource)}}
    @endslot

    <img style="max-width: {{config('app.resource.preview.maxWidth')}}px; max-height: {{config('app.resource.preview.maxHeight')}}px;"
         class="card-img-top img-fluid"
         src="{{ $src }}"
         alt="Preview">

@endcomponent
