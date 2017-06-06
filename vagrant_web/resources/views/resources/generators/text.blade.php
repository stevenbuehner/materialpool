@component('resources.generators.component')
    @slot('title')
        {{$title or class_basename($resource)}}
    @endslot

    @slot('menu')
        <a href="{{route('pool.resource.edit', ['resource' => $resource->id])}}"
           class="btn btn-secondary">@lang("Bearbeiten")</a>
    @endslot

    <pre>
        {{$content or 'no content'}}
    </pre>
@endcomponent
