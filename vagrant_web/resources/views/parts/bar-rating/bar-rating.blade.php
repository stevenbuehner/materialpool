{{--
Parameters:
$rangeStart (int)
$rangeEnd (int)
$steps (int / float)
$value (int)
$name fieldname
$id fieldid (optional)
--}}

@php
    if($rangeEnd < $rangeStart){
    // Switch
    $tmp = $rangeStart;
    $rangeStart = $rangeEnd;
    $rangeEnd = $rangeStart;
    }

   $id =  isset($id) ? $id : uniqid();

$current = $rangeStart;
$count = 0;
@endphp

<select id="{{$id}}" name="{{$name}}">
    @while($current <= $rangeEnd && $count <= 100)
        <option value="{{$current}}"@if($current == $value) {{'selected="selected"'}}@endif>{{$current}}</option>
        @php
            $count++;
        $current += $steps;
        @endphp
    @endwhile
</select>
<script>
    $(function () {
        $('#{{$id}}').barrating({
            theme: 'css-stars'
        });
    });
</script>