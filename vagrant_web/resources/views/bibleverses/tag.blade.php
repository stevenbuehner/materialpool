<a class="btn btn-{{$class or 'secondary'}} btn-{{$size or 'sm'}} tag tag-readonly"
   href="{{route('pool.material.by.bibleverse', ['from' => $bibleverse->from, 'to' => $bibleverse->to])}}" role="button">
    @if($keyword->pivot && isset($bibleverse->pivot->relevance))
        <div class="progress-bar" style="width: {{ round($bibleverse->pivot->relevance / 200 * 100) }}%;"></div>
    @endif
    <span class="icon" style="background-image: url({{$bibleverse->icon}});"></span>
    <span class="text">{{ $bibleverse->label }}</span>
</a>
