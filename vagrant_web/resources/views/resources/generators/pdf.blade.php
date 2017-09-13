@component('resources.generators.component')

    @slot('menu')
        <a href="{{ URL::route('pool.resource.download', [$resource->id]) }}"
           class="btn btn-primary">download ganzes PDF</a>
    @endslot


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
                             alt="Vorschau Seite {{$pageNo}}">
                    </a>
                </div>
            @endfor
        @endif
    </div>

@endcomponent
