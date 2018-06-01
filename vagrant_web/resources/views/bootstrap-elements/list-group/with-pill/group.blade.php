{{--
Required:
- array $items mit [$text => $count]
 --}}

<ul class="list-group">
    @foreach($items as $text => $count)
        @include('bootstrap-elements.list-group.with-pill.item',
            ['text' => $text, 'count' => $count]
        )
    @endforeach
</ul>
