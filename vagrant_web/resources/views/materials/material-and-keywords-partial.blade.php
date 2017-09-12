<div class="card">

    <div class="card-header">
        <div class="btn-group pull-right" role="group" aria-label="Edit Material">
            <a role="button" class="btn btn-secondary btn-sm"
               href="{{ route('pool.material.edit', [$material]) }}">edit</a>
            <a role="button" class="btn btn-secondary btn-sm"
               href="{{ route('pool.material.show', [$material]) }}">show</a>
        </div>
    </div>

    <div class="card-block">

        <h2 class="card-title">
            {{$material->title}}
            @if($material->pivot->limitation instanceof \App\ResourceLimitations\ResourceLimitationInterface)
                (Nur {{ str_limit($material->pivot->limitation->getLimitationText(), 20) }})
            @endif
        </h2>

        <small class="card-">
            {{ $material->created_at->diffForHumans() }}, by {{$material->creator->name}}
        </small>

        <p class="card-text">
            {{ $material->description }}
        </p>

        @if(count($material->keywords) || count($material->bibleverses))
            @if(count($material->keywords))
                @foreach($material->keywords as $keyword)
                    @include('keywords.linked', ['keyword' => $keyword])
                @endforeach
            @endif

            @if(count($material->bibleverses))
                @foreach($material->bibleverses as $bv)
                    @include('bibleverses.tag', ['bibleverse' => $bv])
                @endforeach
            @endif
        @endif

    </div>

</div>