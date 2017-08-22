<div class="col-md-6 pdfpreview-cell">
    <div class="selection-container"></div>

    <div class="image-container">
        <img src="{{route('PdfPreview/ImagePreview', ['fileId' => $fileId, 'page' => $page])}}">
    </div>

    <div class="menue-container">
        <div class="left"></div>
        <div class="middle">{{$page}}</div>
        <div class="right"></div>
    </div>
</div>