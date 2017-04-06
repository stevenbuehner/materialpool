@php
    $selected = (isset($selected)) ? $selected : [];
    $displayField = (isset($displayField)) ? $displayField : 'id';
@endphp

{{--
Required Variables:
- string $url (to paginated select2 ajax source)
- string $displayField (field of json-feed that is to be displayed)
- string $updateRelevanceUrl (the url to PUT the relevance changes .. the keyword-id will be appended)
- string $deleteAssignmentUrl
- string $createKeywordUrl

Optional Variables
- string $name (html ID) - required when multiple fields are used
- array $selected (id => value)
- string $placeholder (Placeholder)
--}}

<select type="text/javascript"
        id="{{$name or 'select2_ajax_multiple'}}"
        name="{{$name or 'select2_ajax_multiple'}}[]"
        multiple="multiple"
        title="{{$placeholder}} or '"
        style="width: 100%">
    @if(isset($selected))
        @foreach($selected as $item)
            <option value="{{$item['id']}}" selected="selected"
                    data-item='{!! $item !!}'>{{$item[$displayField]}}</option>
        @endforeach
    @endif
</select>

<script>

    (function () {

        function formatState(state) {

            if (state.item === undefined) {
                // Override with data() data
                state.item = $(state.element).data('item');
            }

            if (state.item === undefined) {
                state.item = {};
            }

            // shortcut
            var item = state.item;

            // Only init first time
            if (item.saved == undefined) {
                item.saved = (item.pivot === undefined || item.pivot.relevance === undefined) ? false : true;
            }

            var html = $(formatRepo(state));

            var progressbar = $('<div class="progress-bar"></div>');
            var $state      = $('<div/>').append(progressbar).append('<span style="position: relative;"> ' + state.text + '</span>');

            var $state = html.prepend(progressbar);


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

                            createOrUpdateApiAssignment(item.id, val)
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

                    var $buttonDelete = $("<input type='reset' role='button' class='btn btn-danger btn-sm' value='löschen' />").click(function (el) {

                        deleteApiAssignment(id)
                            .done(function () {
                                $select2.find("option[value='" + id + "']").remove();
                                $select2.trigger("change");
                            })
                            .fail(function () {
                                alert("Fehler beim Löschen des Tags");
                            });

                        closePopover();
                    });

                    var $content = $("<div></div>").append($input).append('<br/>').append($buttonCancel).append($buttonDelete).append($buttonSave);

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

        function formatRepo(repo) {
            if (repo.loading)
                return repo.text;

            var html = '<div class="tag tag-selection">';

            // Icon
            if (repo.item && repo.item.icon) {
                html += '<span class="icon" style="background-image: url(' + repo.item.icon + ');"></span>';
            }

            // Text / Label
            html += '<span class="text">' + repo.text + '</span>';

            // New-Badge
            if (repo.newTag === true) {
                html += '<span class="badge badge-default small">neu</span>';
            }

            html += '</div>';

            return html;
        }

        function createApiKeyword(title, type) {
            var url                       = "{{$createKeywordUrl}}";
            var postData                  = {};
            postData["{{$displayField}}"] = title;

            if (type !== undefined) {
                postData.title = type;
            }

            return $.post(url, postData);
        }

        function createOrUpdateApiAssignment(keywordId, relevance) {
            var url  = "{{$updateRelevanceUrl}}" + keywordId;
            var data = {
                _method: 'PUT'
            };

            if (relevance !== undefined) {
                data.relevance = relevance;
            }

            return $.post(url, data);
        }

        function deleteApiAssignment(keywordId) {
            var url  = "{{$deleteAssignmentUrl}}" + keywordId;
            var data = {_method: 'DELETE'};

            return $.post(url, data);
        }

        function aktualisiereOptionMetaData(optionId, metaData) {
            var $option = $select2.find('option[value="' + optionId + '"]');
            var state   = $option.data('data');

            // Aktualisiere Id
            if (metaData.id !== undefined) {
                $option.val(metaData.id);
                state.id = metaData.id;

                // Jetzt ist es kein "newTag" mehr
                state.newTag = false;
            }

            // Aktualisiere Label
            if (metaData["{{$displayField}}"] !== undefined) {
                $option.text(metaData["{{$displayField}}"]);
                state.text = metaData["{{$displayField}}"];
            }

            // Aktualisiere MetaData
            $option.data('item', metaData); // just for conistency
            state.item = metaData;
            $select2.trigger("change");
        }


        var $select2 = $("#{{$name or 'select2_ajax_multiple'}}").select2({
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
            templateSelection: formatState, // Rendering the stored tags
            templateResult: formatRepo,
            escapeMarkup: function (markup) {
                return markup;
            }, // Let templateResult be rendered as html
            tags: true,
            createTag: function (params) {
                var term = $.trim(params.term);

                if (term === '') {
                    return null;
                }

                var uniq = 'new_' + (new Date()).getTime();

                return {
                    id: uniq,
                    text: term,
                    newTag: true // add additional parameters
                }
            }

        }).on('select2:select', function (e) {
            var oldData = e.params.data;
            var oldId   = oldData.id;

            if (oldData.newTag === true) {
                createApiKeyword(oldData.text)
                    .done(function (resultData) {
                        var newId = resultData.id;

                        aktualisiereOptionMetaData(oldId, resultData);

                        createOrUpdateApiAssignment(newId)
                            .done(function (assignmentResult) {
                                aktualisiereOptionMetaData(newId, assignmentResult);
                            })
                            .fail(function () {
                                alert("Konnte das erstellte Tag nicht diesem Material zuweisen");
                            });
                    })
                    .fail(function (resultData) {
                        alert('Konnte Tag nicht erstellen: ' + oldData.text)
                    });

            } else {

                createOrUpdateApiAssignment(oldId)
                    .done(function (assignmentResult) {
                        aktualisiereOptionMetaData(oldId, assignmentResult);
                    })
                    .fail(function () {
                        alert("Konnte das Tag nicht diesem Material zuweisen");
                    });
            }
        });

    })($);

</script>

