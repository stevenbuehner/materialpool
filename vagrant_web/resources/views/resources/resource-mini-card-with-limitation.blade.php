<div class="card">

    <div class="card-block">
        @include('resources.partials.filename-or-classname')
        @if($resource instanceof \App\Models\PdfFile && $resource->pivot->limitation instanceof \App\ResourceLimitations\PageLimitation)
            <br/>(Seiten: {{ join(', ', $r->pivot->limitation->getPages())}})
        @endif
    </div>


    <div class="card-footer">
        <div class="btn-group pull-right" role="group" aria-label="Edit Material">
            <a role="button" class="btn btn-secondary btn-sm"
               href="{{ route('pool.resource.edit', [$resource]) }}">bearbeiten</a>
            <a role="button" class="btn btn-secondary btn-sm"
               href="{{ route('pool.resource.show', [$resource]) }}">öffnen</a>
        </div>
    </div>

</div>
