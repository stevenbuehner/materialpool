@extends('layouts.app')

@section('content')




    <div class="row">
        <div class="col-sm-10" id="searchbar_search_row">
        </div>
        <div class="col-sm-2">
            <button id="testbutton" class="btn btn-default">+</button>
        </div>
    </div>

    <hr>

    <div class="row" id="searchbar_content_row">
        <material-list v-bind:materials="materials"></material-list>
    </div>

    <script type="text/javascript">

        var searchbar = (function () {
            var $searchTemplate     = $('<select id="search_template" name="search_template" class="col-sm-12" multiple="multiple" style="width:100%; display: none;"></select>');
            var $searchbarSearchRow = $("#searchbar_search_row");
            var $searchBars         = $();
            var $searchbarContent   = $("#searchbar_content_row");

            var addSearchbar = function () {
                $newBar = copySearchbarTemplate();
                setupSelect2($newBar);
            };

            var getQueryData = function () {
                var result = {};

                $searchBars.each(function (selectIndex) {

                    result[selectIndex] = {};

                    $(this).find('option').each(function (optionIndex) {
                        var optionData = $(this).data('data');

                        if (optionData.item) {
                            result[selectIndex][optionIndex] = optionData.item;
                        }

                    });
                });

                return result;
            };

            function selectionUpdated() {
                console.log('Selection updated');
                updateSearchResultContent();
            }

            function updateSearchResultContent() {


                testVue.updateMaterialList(getQueryData())

            }

            function copySearchbarTemplate() {
                var $clone = $searchTemplate.clone();
                $clone.prop('name', getSearchbarName($searchBars.length));

                $clone.appendTo($searchbarSearchRow);
                $searchBars = $searchBars.add($clone);
                $clone.wrap('<div class="row"/>');
                $clone.show();

                console.log('Copied Searchbar', $clone);

                return $clone;
            }

            /**
             * get the name of the select2 Searchbar
             * @param index
             * @param element
             * @return {string}
             */
            function getSearchbarName(index) {
                var s = 'q[' + index + ']';

                return s;
            }

            function setupSelect2($select) {

                function formatListing(item) {
                    if (item.loading)
                        return item.text;

                    var html = '<div class="tag tag-selection">';

                    // Icon
                    if (item.icon) {
                        html += '<span class="icon" style="background-image: url(' + item.icon + ');"></span>';
                    }

                    // Text / Label
                    html += '<span class="text">' + item.text + '</span>';

                    html += '</div>';

                    return html;
                }

                $select.select2({
                    ajax: {
                        url: "{{route('pool.searchbar.guess')}}",
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
                                        text: item.text,
                                        id: 'quid' + (new Date()).getTime(),
                                        icon: item.icon,
                                        item: item.item
                                    }
                                }),
                                pagination: {
                                    more: data.data.length == data.per_page // data.data.length == data.per_page
                                }
                            };
                        },
                        cache: true,
                    },
                    minimumInputLength: 1,
                    placeholder: "Suchbegriffe eingeben",
                    theme: 'bootstrap',
                    templateResult: formatListing,
                    templateSelection: formatListing,
                    escapeMarkup: function (markup) {
                        return markup;
                    }, // Let templateResult be rendered as html
                }).on('change', selectionUpdated);
            }


            return {
                addSearchbar: addSearchbar,
                getQueryData: getQueryData,
                forceUpdate: updateSearchResultContent
            }
        })
        ($);

        searchbar.addSearchbar();

    </script>

    <script>
        $("#testbutton").on('click', function () {
            searchbar.addSearchbar();
        });




    </script>
    <script src="{{ mix('/js/searchbar.js') }}"></script>


@endsection