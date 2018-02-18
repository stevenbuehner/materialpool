@component('resources.generators.component')


    @push("styles")
        <link rel="stylesheet" href="/vendor/pdfpreview/css/pdfpreview.css" xmlns:v-on="http://www.w3.org/1999/xhtml"
              xmlns:v-on="http://www.w3.org/1999/xhtml">
    @endpush

    @push("scripts")
        <script src="/vendor/pdfpreview/js/pdfpreview.js"></script>
    @endpush


    @slot('title')
        {{$title}}
    @endslot

    @slot('menu')
        <a href="{{ URL::route('pool.resource.download', [$resource->id]) }}"
           class="btn btn-secondary">@lang('pool.download-whole-pdf')</a>
    @endslot


    <div id="pdfpreview-app">
        <image-zoomer ref="zoomer"></image-zoomer>
        <div class="row">
            @if($limitation)
                @foreach($limitation->getPages() as $pageNo)
                    <div class="col-lg-3 col-md-4 col-xs-6">
                        <a href="#" class="d-block mb-4 h-100">
                            <img class="img-fluid img-thumbnail"
                                 src="{{ route('PdfPreview/ImagePreview',
							['fileId' => $resource->id,
							 'page'    => $pageNo])
						}}"
                                 alt="Vorschau Seite {{$pageNo}}">
                        </a>
                    </div>
                @endforeach
            @else
                @for($pageNo = 1; $pageNo <= $totalPageCount; $pageNo++)
                    <div class="col-lg-3 col-md-4 col-xs-6">
                        <a href="#" class="d-block mb-4 h-100">
                            <img class="img-fluid img-thumbnail"
                                 src="{{ route('PdfPreview/ImagePreview',
							['fileId' => $resource->id,
							 'page'    => $pageNo])
						}}"
                                 v-on:click="openZoom"
                                 alt="Vorschau Seite {{$pageNo}}"
                                 data-preview-link="{{ route('PdfPreview/ImagePreview',
							['fileId' => $resource->id,
							 'page'    => $pageNo])
						}}">
                        </a>
                    </div>
                @endfor
            @endif
        </div>
    </div>

    <script>
        new Vue({
            el: '#pdfpreview-app',
            data: {},

            mounted: function () {
                // EventHandler.$on('zoomInRequested', this.$refs.zoomer.showImage);
            },

            methods: {
                openZoom: function (event) {
                    var link = $(event.target).data('previewLink');
                    this.$refs.zoomer.showImage(link);
                }
            }
        });
    </script>

@endcomponent
