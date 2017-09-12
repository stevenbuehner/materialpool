@extends('layouts.app')

@push("styles")
    <link rel="stylesheet" href="/vendor/pdfpreview/css/pdfpreview.css">
@endpush

@push("scripts")
    <script src="/vendor/pdfpreview/js/pdfpreview.js"></script>
    <script src="/js/pdf.assign.material.js"></script>
@endpush

@section('content')

    <div id="assign-pdf-app">
        <div class="row">
            <div class="col-7">
                <h1>Seitenauswahl</h1>
                <div id="pdfpreview-app">
                    <image-zoomer ref="zoomer"></image-zoomer>
                    <page-list ref="pagelist"
                               :file-id="{{$fileId}}"
                               :page-count="{{$pageCount}}"
                               @if($imagePreviewRoute)
                               preview-link-pattern="{!! $imagePreviewRoute !!}"
                            @endif
                    >
                    </page-list>
                </div>
            </div>
            <div class="col-5" id="pdfPrev">
                <h1>
                    Material Voreinstellungen
                </h1>

                {!! Form::open(['route' => ['pool.material.store'], 'files' => FALSE, 'method' => 'POST', 'target'=>'_blank']) !!}

                {!! Form::hidden('resources[]', $fileId) !!}

                <div class="form-group">
                    {!! Form::label('title', 'Titel:') !!}
                    {!! Form::text('title', '', ['class' => 'form-control', 'required' => TRUE, 'placeholder' => 'Titel' ]) !!}
                </div>


                <div class="form-group">
                    {!! Form::label('rating', 'Bewertung:') !!}
                    @include('parts.bar-rating.bar-rating', [
                        'rangeStart' => 0,
                        'rangeEnd' => 20,
                        'steps' => 2,
                        'name' => 'rating',
                        'value' => 10
                    ])
                </div>


                <div class="form-group">
                    {!! Form::label('description', 'Beschreibung:') !!}
                    {!! Form::textarea('description', '', ['class' => 'form-control', 'required' => FALSE ]) !!}
                </div>


                {!! Form::hidden('from_bot', 1, FALSE) !!}

                <div class="form-group">
                    {!! Form::label('meta', 'Schlagwörter und Bibelverse') !!}
                    {!! Form::textarea('meta', '', ['class' => 'form-control', 'required' => TRUE]) !!}
                </div>

                <div class="form-group">
                    {!! Form::label('limitation', 'Limitierung:') !!}
                    @{{pages}}
                    <input type="hidden" name="limit[{{$fileId}}][type]" value="page"/>
                    <input type="hidden" name="limit[{{$fileId}}][value]" v-model="pageIndex" id="limitation"/>
                </div>


                <div class="form-group">
                    {!! Form::submit('zur Materialerstellung', ['class' => 'btn btn-primary']) !!}
                </div>


                {!! Form::close() !!}

            </div>
        </div>
    </div>


    <script>
        new Vue({
            el: '#assign-pdf-app',
            data: {selectedPages: []},

            computed: {
                pageIndex: function () {
                    var sel = [];

                    for (var i in this.selectedPages) {
                        sel.push(this.selectedPages[i].index);
                    }

                    return sel;
                },

                pages: function () {
                    var sel = [];

                    var start  = false, stop = false;
                    var plural = false;

                    for (var i in this.selectedPages) {
                        if (stop !== false) {
                            plural = true;

                            if (this.selectedPages[i].index - 1 == stop) {
                                stop++;
                            } else if (start == stop) {
                                sel.push(start);
                                start = stop = this.selectedPages[i].index;
                            } else {
                                sel.push(start + '-' + stop);
                                start = stop = this.selectedPages[i].index;
                            }
                        } else {
                            start = stop = this.selectedPages[i].index;
                        }
                    }

                    if (stop !== false) {
                        if (start == stop) {
                            sel.push(start);
                        } else {
                            sel.push(start + '-' + stop);
                        }
                    }

                    var result = '';
                    if (sel.length > 0) {
                        result = plural ? 'Seiten ' : 'Seite ';
                        result += sel.join(', ');
                    }

                    return result;
                }
            },

            created: function () {
                EventHandler.$on('selection.update', this.selectionChanged)
            },

            mounted: function () {
                EventHandler.$on('zoomInRequested', this.$refs.zoomer.showImage);
            },

            methods: {
                selectionChanged: function (selectedPages) {
                    this.selectedPages = selectedPages;
                }
            }
        });
    </script>


@endsection