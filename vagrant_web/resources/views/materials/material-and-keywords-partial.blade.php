<div class="card">

    <div class="card-block">
        <h1>{{$material->title}}</h1>
        <small>{{ $material->created_at->diffForHumans() }}</small>

        <p class="lead">{{ $material->description }}</p>
    </div>

    @if(count($material->keywords) || count($material->bibleverses))
        <div class="card-block">
            @if(count($material->keywords))
                <div class="card-block">
                    @foreach($material->keywords as $keyword)
                        @include('keywords.linked', ['keyword' => $keyword])
                    @endforeach
                </div>
            @endif

            @if(count($material->bibleverses))
                <div class="card-block">
                    @foreach($material->bibleverses as $bv)
                        @include('bibleverses.tag', ['bibleverse' => $bv])
                    @endforeach
                </div>
            @endif
        </div>
    @endif

</div>