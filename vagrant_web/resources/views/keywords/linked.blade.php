<a class="btn btn-{{$class or 'secondary'}} btn-{{$size or 'sm'}} tag tag-readonly"
   href="{{route('pool.material.by.keyword', ['keyword' => $keyword->lc_title])}}" role="button">
    @if($keyword->pivot && isset($keyword->pivot->relevance))
        <div class="progress-bar" style="width: {{ round($keyword->pivot->relevance / \App\Services\TagExtraction\Interfaces\RelevanceInterface::RELEVANCE_USER_MAX * 100) }}%;"></div>
    @endif
    <span class="icon" style="background-image: url({{$keyword->icon}});"></span>
    <span class="text">{{ $keyword->title }}</span>
</a>
