<a class="btn btn-{{$class or 'secondary'}} btn-{{$size or 'sm'}}"
   href="{{route('bibleverse', ['from' => $bibleverse->from, 'to' => $bibleverse->to])}}"
   role="button">{{ $bibleverse->label }}</a>
