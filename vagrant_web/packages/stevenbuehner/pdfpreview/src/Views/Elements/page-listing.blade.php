@push("styles")
    <link rel="stylesheet" href="/pdfpreview.css">
@endpush

@push("scripts")
    <script src="/pdfpreview.js"></script>
@endpush

<div class="container pdfpreview">
    <div class="content">
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
    </div>
</div>


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
