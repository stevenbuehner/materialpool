<a class="btn btn-{{$class or 'secondary'}} btn-{{$size or 'sm'}}"
   href="{{route('keyword', ['keyword' => $keyword->lc_title])}}"
   role="button">{{ $keyword->title }}</a>
