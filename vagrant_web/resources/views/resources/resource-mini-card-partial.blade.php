<div class="card">

    <div class="card-body">
        {{ class_basename($resource)}}-Resource

        @if(!empty($resource->notes))
            <small>
                {{ str_limit($resource->notes, 100) }}
            </small>
        @endif
    </div>

    <div class="card-footer">
        <div class="btn-group pull-right" role="group" aria-label="Edit Material">
            <a role="button" class="btn btn-secondary btn-sm"
               href="{{ route('pool.resource.edit', [$resource]) }}">bearbeiten</a>
            <a role="button" class="btn btn-secondary btn-sm"
               href="{{ route('pool.resource.show', [$resource]) }}">zeigen</a>
        </div>
    </div>

</div>
