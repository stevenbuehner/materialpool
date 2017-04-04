@php
    $selected = (isset($selected)) ? $selected : [];
    $displayField = (isset($displayField)) ? $displayField : 'id';
@endphp

{{--
Required Variables:
- string $url (to paginated select2 ajax source)
- string $displayField (field of json-feed that is to be displayed)
- string $updateRelevanceUrl (the url to PUT the relevance changes .. the keyword-id will be appended)
- string $createAssignmentUrl

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
        <option value="{{$item['id']}}" selected="selected" data-item='{!! $item !!}'>{{$item[$displayField]}}</option>
    @endforeach
</select>

<script>
    function formatState(state) {
        var item = {};


        if (state.element) {
            item = $(state.element).data('item');
        }

        if (item === undefined && state.item) {
            item = state.item;
        }

        // Only init first time
        if (item.saved == undefined) {
            item.saved = (item.pivot === undefined || item.pivot.relevance === undefined) ? false : true;
        }

        var progressbar = $('<div class="progress-bar"></div>');
        var $state      = $('<div/>').append(progressbar).append('<span style="position: relative;"> ' + state.text + '</span>');
        updateItemRelevance();

        function initPopover() {
            $state.one('click', function (el) {
                el.stopPropagation();

                if (item.saved === false) {
                    initPopover();
                    alert('Warte bitte kurz, bis der ketzte Wert gespeichert wurde ...');
                    return;
                }

                var lastRelevance = item.pivot.relevance;
                var id            = item.id;

                var $input = $("<input data-id='" + id + "' type='range' min='0' max='200' step='1' value='" + lastRelevance + "'/>")
                    .change(function (el) {
                        var val = parseInt($input.val());
                        updateItemRelevance(val);
                    })
                    .on('input', function (el) {
                        updateItemRelevance(parseInt($input.val()));
                    });

                var $buttonCancel = $("<input type='reset' role='button' class='btn btn-default btn-sm' value='abbrechen' />").click(function (el) {
                    updateItemRelevance(lastRelevance);
                    closePopover();
                });

                var $buttonSave = $("<input type='submit' role='button' class='btn btn-primary btn-sm' value='speichern' />").click(function (el) {

                    var val = parseInt($input.val());
                    if (val != lastRelevance) {
                        item.saved = false;
                        updateItemRelevance();

                        var url  = "{{$updateRelevanceUrl}}" + item.id;
                        var data = {relevance: val, _method: 'PUT'};

                        $.post(url, data)
                            .done(function (data) {
                                item.saved = true;
                                // Update all values
                                for (i in data) {
                                    item[i] = data[i];
                                }
                                updateItemRelevance(data.pivot.relevance);
                            })
                            .fail(function () {
                                alert("Update konnte nicht durchgeführt werden! Bitte Seite neu laden");
                            });
                    }

                    closePopover();
                });

                var $content = $("<div></div>").append($input).append('<br/>').append($buttonCancel).append($buttonSave);

                $state.popover({
                    content: $content,
                    animation: false,
                    html: true,
                    placement: 'top',
                    title: 'Relevanz-Einstellungen',
                    trigger: ''
                }).popover('show');
            })
        }

        initPopover();

        function closePopover() {
            $state.popover('dispose');
            initPopover();
        }

        function updateItemRelevance(value) {

            if (item.saved) {
                progressbar.removeClass('bg-warning');
            } else {
                progressbar.addClass('bg-warning');
            }

            // If relevance exists but was not given as parameter
            if (value == undefined && (item.pivot != undefined && item.pivot.relevance != undefined)) {
                value = item.pivot.relevance;
            }

            if (value === undefined) {
                progressbar.css('width', '100%');
            } else {
                progressbar.css('width', Math.round(value / 200 * 100, 1) + '%');
                item.pivot.relevance = value;
            }
        }

        return $state;
    }

    function createKeyword(data, callbackSuccess, callbackFailure) {
        $.post("{{$createAssignmentUrl}}", data)
            .done(callbackSuccess)
            .fail(callbackFailure);
    }

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
                            id: item.id,
                            item: item
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
        theme: 'bootstrap',
        templateSelection: formatState,

    }).on('select2:select', function (e) {
        var data     = e.params.data;
        var id       = data.id || '';
        var url      = "{{$createAssignmentUrl}}" + id;
        var $select2 = $(e.target);

        console.log(e);
        console.log($select2);
        $.post(url, data.item)
            .done(function (result) {
                $option = $select2.find('option[value="' + id + '"]');
                $option.data('item', result);
                // data.item = result; // Does not work :-(
                console.log($option);
                $select2.trigger("change");
            })
            .fail(function (result) {

            });

    });
</script>

