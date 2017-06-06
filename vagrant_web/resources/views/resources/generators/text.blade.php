@component('resources.generators.component')
    @slot('title')
        {{$title or class_basename($resource)}}
    @endslot

    @slot('menu')
        <a href="{{route('pool.resource.edit', ['resource' => $resource->id])}}"
           class="btn btn-secondary">@lang("Bearbeiten")</a>
        <a href="{{ URL::route('pool.resource.download', [$resource->id]) }}"
           class="btn btn-primary">download</a>
    @endslot

    <pre>
        {{$content or 'no content'}}
    </pre>
@endcomponent
