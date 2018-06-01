@component('resources.generators.component')
    @slot('title')
        {{$title or class_basename($resource)}}
    @endslot

    @slot('menu')
        <a href="{{route('pool.resource.edit', ['resource' => $resource->id])}}"
           class="btn btn-secondary">@lang("Datei Bearbeiten")</a>
        <a href="{{ URL::route('pool.resource.download', [$resource->id]) }}"
           class="btn btn-secondary">Datei herunterladen</a>
    @endslot

    <pre class="text-wrap">{{$content or 'no content'}}</pre>
@endcomponent
