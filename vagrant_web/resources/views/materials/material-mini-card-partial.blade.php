<div class="card">

    <div class="card-body">
        <div class="card-title">
            {{ str_limit($material->title, 50)}}
        </div>

        <small>
            {{ str_limit($material->description, 100) }}
        </small>

    </div>

    <div class="card-footer">
        <div class="btn-group pull-right" role="group" aria-label="Edit Material">
            <a role="button" class="btn btn-secondary btn-sm"
               href="{{ route('pool.material.edit', [$material]) }}">bearbeiten</a>
            <a role="button" class="btn btn-secondary btn-sm"
               href="{{ route('pool.material.show', [$material]) }}">zeigen</a>
        </div>
    </div>

</div>
