{{--
Required:
- string $text
- int $count
 --}}
<li class="list-group-item d-flex justify-content-between align-items-center @if($count == 0) disabled @endif">
    {{$text}}
    <span class="badge badge-primary badge-pill">{{$count}}</span>
</li>