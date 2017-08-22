@extends('layouts.app')

@section('content')
    <div class="container pdfpreview">
        <div class="content">
            <div class="title">Old: {{ $fileId }}</div>

            @for($page = $startPage; $page <= $endPage; $page++)
                <div class="row pdfpreview-row">

                </div>
            @endfor


        </div>
    </div>

    <div id="pdfpreview-app">
        <page-list></page-list>
        <span>@{{message}}</span>
    </div>


    <script src="/pdfpreview.js"></script>

    @push("styles")
        <link rel="stylesheet" href="/pdfpreview.css">
    @endpush


    <script>

        var data = {message: "test"};

        new Vue({
            data: data,
            el: '#pdfpreview-app'
        });

    </script>

@endsection
