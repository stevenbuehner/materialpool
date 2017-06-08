@component('resources.generators.component')
    @slot('title')
        {{ $title or class_basename($resource) }}
    @endslot

    @slot('menu')
        <a href="{{ URL::route('pool.resource.download', [$resource->id]) }}"
           class="btn btn-primary">download</a>
    @endslot

    @push('scripts')
    <script src="//vjs.zencdn.net/4.12/video.js"></script>
    @endpush

    @push('styles')
    <link href="//vjs.zencdn.net/4.12/video-js.css" rel="stylesheet">
    @endpush


    <video id="video_file" class="video-js vjs-default-skin vjs-big-play-centered"
           controls preload="auto" height="600" width="980">

        <source src="{{ route('pool.resource.videostream', [$resource->id]) }}"
                type="{{ $resource->getLocalMimeType() }}"/>
    </video>

    <script>
        videojs(document.getElementById('video_file'), {}, function () {
            // This is functionally the same as the previous example.
        });
    </script>


@endcomponent
