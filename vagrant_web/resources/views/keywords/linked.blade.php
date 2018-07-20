<keyword title="{{$keyword->title}}"
         lc_title="{{$keyword->lc_title}}"
         @if($keyword->pivot && isset($keyword->pivot->relevance))
         v-bind:relevance="{{$keyword->pivot->relevance}}"
         @endif
         icon="{{$keyword->icon}}"
         class="secondary"
         searchlink="{{route('pool.material.by.keyword', ['keyword' => $keyword->lc_title])}}">
</keyword>
