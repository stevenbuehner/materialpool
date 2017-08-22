@extends('layouts.app')

@section('content')
    <div class="container pdfpreview">
        <div class="content">
            <div class="title">File-ID: {{ $fileId }}</div>
        </div>
    </div>

    <div id="pdfpreview-app">
        <image-zoomer ref="zoomer"></image-zoomer>
        <page-list
                file-id="{{$fileId}}"
                page-count="{{$pageCount}}"
                @if($imagePreviewRoute)
                preview-link-pattern="{!! $imagePreviewRoute !!}"
                @endif
        >
        </page-list>
    </div>

    <script src="/pdfpreview.js"></script>

    @push("styles")
        <link rel="stylesheet" href="/pdfpreview.css">
    @endpush


    <script>
        new Vue({
            el: '#pdfpreview-app',
            created: function () {
            },

            mounted: function () {
                EventHandler.$on('zoomInRequested', this.$refs.zoomer.showImage);
            }
        });
    </script>

@endsection
