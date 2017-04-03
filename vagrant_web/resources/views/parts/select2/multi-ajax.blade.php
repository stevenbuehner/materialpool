@php
    $selected = (isset($selected)) ? $selected : [];
    $displayField = (isset($displayField)) ? $displayField : 'id';
@endphp

{{--
Required Variables:
- string $url (to paginated select2 ajax source)
- string $displayField (field of json-feed that is to be displayed)

Optional Variables
- string $name (html ID)
- array $selected (id => value)
- string $placeholder (Placeholder)
--}}

<select type="text/javascript"
        id="{{$name or 'select2_ajax_multiple'}}"
        name="{{$name or 'select2_ajax_multiple'}}[]"
        multiple="multiple"
        title="{{$placeholder}}"
        style="width: 100%">
    @foreach($selected as $item)
        <option value="{{$item['id']}}" selected="selected">{{$item[$displayField]}}</option>
    @endforeach
</select>

<script>
    $("#{{$name or 'select2_ajax_multiple'}}").select2({
        ajax: {
            url: "{{$url}}",
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // search term
                    page: params.page
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;

                return {
                    results: $.map(data.data, function (item) {
                        return {
                            text: item["{{ $displayField }}"],
                            id: item.id
                        }
                    }),
                    pagination: {
                        more: data.current_page < data.last_page
                    }
                };
            },
            cache: true
        },
        minimumInputLength: 1,
        placeholder: "{{$placeholder}}",
        theme: 'bootstrap'
    });
</script>
