@extends('layouts.app')

@section('content')


    <select id="search_template" name="search_template" class="col-sm-12" multiple="multiple"
            style="width:100%; display: none;">

    </select>

    <div class="row">
        <div class="col-sm-10" id="searchbar_search_row">
        </div>
        <div class="col-sm-2">
            <button id="testbutton" class="btn btn-default">Some</button>
        </div>
    </div>

    <hr>

    <div class="row" id="searchbar_content_row">

    </div>

    <script type="text/javascript">

        var searchbar = (function () {
            var $searchTemplate     = $("#search_template");
            var $searchbarSearchRow = $("#searchbar_search_row");
            var $searchBars         = $();

            var addSearchbar = function () {
                $newBar = copySearchbarTemplate();
                setupSelect2($newBar);
            };

            var getQueryData = function () {

            };

            function copySearchbarTemplate() {
                var $clone = $searchTemplate.clone();
                $clone.prop('name', getSearchbarName($searchBars.length));

                $clone.appendTo($searchbarSearchRow);
                $searchBars.add($clone);
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
            function getSearchbarName(index, element) {
                var s = 'q[' + index + ']';

                if (element !== undefined) {
                    s += '[' + element + ']';
                }

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

                            console.log(data.data.length == data.per_page);
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
                                    more: true // data.data.length == data.per_page
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
                });
            }


            return {
                addSearchbar: addSearchbar,
                getQueryData: getQueryData
            }
        })($);

        searchbar.addSearchbar();

    </script>

    <script>
        $("#testbutton").on('click', function () {
            searchbar.addSearchbar();
        });
    </script>


@endsection