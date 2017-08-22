@extends('layouts.app')

@section('content')
    <div class="container pdfpreview">
        <div class="content">
            <div class="title">File-ID: {{ $fileId }}</div>
        </div>
    </div>

    <div id="pdfpreview-app">
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
            el: '#pdfpreview-app'
        });
    </script>

@endsection
