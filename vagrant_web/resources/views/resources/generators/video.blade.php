@component('resources.generators.component')
    @slot('title')
        {{ $title or class_basename($resource) }}
    @endslot

    @slot('menu')
        <a href="{{ URL::route('pool.resource.download', [$resource->id]) }}"
           class="btn btn-primary">download</a>
    @endslot

    @push('scripts')
    <script src="{{ mix('/js/media.js') }}"></script>
    @endpush

    @push('styles')
    <link href="{{ mix('/css/media.css') }}" rel="stylesheet">
    @endpush


    <video id="video_file" class="video-js vjs-default-skin vjs-big-play-centered"
           controls preload="auto"
           data-setup='{"fluid": true}'
           poster="{{route('resource.image.preview', [$resource->id])}}">

        <source src="{{ route('pool.resource.videostream', [$resource->id]) }}"
                type="{{ $resource->getLocalMimeType() }}"/>
    </video>

    <script>
        videojs(document.getElementById('video_file'), {});
    </script>


@endcomponent
