@component('resources.generators.component')
    @slot('title')
        @if($context == 'material')
            {{ $title or class_basename($resource) }}
        @endif
    @endslot

    @slot('menu')
        @if($context == 'material')
            <a href="{{ URL::route('pool.resource.download', [$resource->id]) }}"
               class="btn btn-sm btn-secondary">download</a>
        @endif

    @endslot

    <img style="max-width: {{config('app.resource.preview.maxWidth')}}px; max-height: {{config('app.resource.preview.maxHeight')}}px;"
         class="card-img-top img-fluid"
         src="{{ $src }}"
         alt="Preview">

@endcomponent
