@component('resources.generators.component')
    @slot('title')
        {{$title or class_basename($resource)}}
    @endslot

    <div>
        {{str_limit($content, 250)}}
    </div>

@endcomponent
