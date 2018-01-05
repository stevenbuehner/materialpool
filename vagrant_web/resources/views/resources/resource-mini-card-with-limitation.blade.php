<div class="card" style="width: 15rem;">

    <img class="card-img-top"
         src="{{route('resource.image.preview', ['resource' => $resource->id, 'width' => 300, 'height' => 300])}}"
         alt="Resource Image"
         style="width:100%;"/>


    <div class="card-body">
        <h4 class="card-title">
            @include('resources.partials.filename-or-classname')
            @if($limitation instanceof \App\ResourceLimitations\ResourceLimitationInterface)
                (Nur {{ str_limit($limitation->getLimitationText(), 20) }})
            @endif
        </h4>
    </div>


    <div class="card-footer">
        <div class="btn-group pull-right" role="group" aria-label="Edit Material">
            <a role="button" class="btn btn-secondary btn-sm" href="{{ route('pool.resource.show', [$resource]) }}">öffnen</a>
            <a role="button" class="btn btn-secondary btn-sm" href="{{ route('pool.resource.edit', [$resource]) }}">bearbeiten</a>
            <a role="button" class="btn btn-secondary btn-sm" href="{{ route('pool.resource.show', [$resource]) }}">Zuordnung
                aufheben</a>
        </div>
    </div>

</div>
