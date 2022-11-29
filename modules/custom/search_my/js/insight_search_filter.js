/**
 * @file
 * Implements the filters for search.
 */

YUI().use('autocomplete-base', 'autocomplete-filters', function (Y) {
    // Create a custom PieFilter class that extends AutoCompleteBase.
    var PieFilter = Y.Base.create('pieFilter', Y.Base, [Y.AutoCompleteBase], {
        initializer: function () {
            this._bindUIACBase();
            this._syncUIACBase();
        }
    }),
            // Create and configure an instance of the PieFilter class.
            filter = new PieFilter({
                inputNode: '#therapy_quick_search',
                minQueryLength: 0,
                queryDelay: 0,

                // Run an immediately-invoked function that returns an array of results to
                // be used for each query, based on the photos on the page. Since the list
                // of photos remains static, this saves time by not gathering the results
                // for each query.
                //
                // If the list of results were not static, we could simply set the source
                // to the function itself rather than invoking the function immediately,
                // and it would then run on every query.
                source: (function () {
                    var results = [];

                    // Build an array of results containing each photo in the list.
                    Y.all('ul#ta-selector > li, ul.facetapi-facet-field-therapy-area-disease li').each(function (node) {
                        results.push({
                            node: node,
                            tags: node.getAttribute('data-therapy-filter')
                        });
                    });

                    return results;
                }),

                // Note the parens. This invokes the function immediately.
                // Remove these to invoke the function on every query instead.
                // Specify that the "tags" property of each result object contains the text
                // to filter on.
                resultTextLocator: 'tags',

                // Use a result filter to filter the photo results based on their tags.
                resultFilters: 'phraseMatch'
            });

    // Subscribe to the "results" event and update photo visibility based on
    // whether or not they were included in the list of results.
    filter.on('results', function (e) {
        // First hide all the therapy areas.
        Y.all('ul#ta-selector li:not(.hide)').addClass('quick_search_hidden');
        Y.all('.facetapi-facet-field-therapy-area-disease li:not(.hide)').addClass('filter-hidden').removeClass('filter-show').removeClass('show');
        Y.all('.facetapi-facet-field-therapy-area-disease li').removeClass('filtered-disease').removeClass('filtered-parent');

        Y.Array.each(e.results, function (result) {
            var search_string = Y.one('#therapy_quick_search').get('value');
            if (result.raw.node.hasClass('quick_search_hidden')) {
                result.raw.node.removeClass('quick_search_hidden');
                var element_class = result.raw.node._node.id;

                if (!(result.raw.node.hasClass('hide'))) {
                    if (search_string === "") {
                        if (!(Y.all('ul.facetapi-facet-field-therapy-area-disease li.' + element_class).hasClass('hide'))) {
                            Y.all('ul.facetapi-facet-field-therapy-area-disease li.' + element_class).removeClass('filter-show').removeClass('filter-hidden').addClass('show');
                        }
                    } else {
                        Y.one('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class).addClass('filtered-parent');
                        Y.all('ul.facetapi-facet-field-therapy-area-disease li.' + element_class).addClass('filtered-disease');
                        if (!(Y.all('ul.facetapi-facet-field-therapy-area-disease li.' + element_class).hasClass('hide')) || !(Y.all('ul.facetapi-facet-field-therapy-area-disease li.' + element_class).hasClass('filter-hidden'))) {
                            Y.all('ul.facetapi-facet-field-therapy-area-disease li.' + element_class).addClass('filter-show').removeClass('filter-hidden');
                        } else if (Y.one('ul#ta-selector li#all-disease').hasClass('active')) {
                            Y.all('ul.facetapi-facet-field-therapy-area-disease li.' + element_class).addClass('filter-show').removeClass('filter-hidden');
                        }
                    }
                }

                if (!(Y.all('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class).hasClass('hide'))) {
                    Y.all('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class).removeClass('filter-hidden').addClass('filter-show');
                }
            } else if (result.raw.node.hasClass('filter-hidden') && !(result.raw.node.hasClass('hide'))) {
                var element_class = result.raw.node._node.className.split(' ')[0];
                if (search_string !== "") {
                    result.raw.node.addClass('filtered-disease');
                    Y.one('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class).addClass('filtered-parent');
                }

                if (Y.one('ul#ta-selector li#' + element_class) && Y.one('ul#ta-selector li#' + element_class).hasClass('active')) {
                    if (!(result.raw.node.hasClass('hide'))) {
                        result.raw.node.removeClass('filter-hidden');
                        Y.one('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class).removeClass('filter-hidden');
                        if (search_string === "") {
                            result.raw.node.removeClass('filter-show').addClass('show');
                            Y.one('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class).removeClass('filter-show').addClass('show');
                        } else {
                            result.raw.node.addClass('filter-show');
                            Y.one('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class).addClass('filter-show');
                        }
                    }
                } else {
                    if (!(result.raw.node.hasClass('hide')) && Y.one('ul#ta-selector li#all-disease').hasClass('active')) {
                        result.raw.node.removeClass('filter-hidden');
                        if (Y.one('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class)) {
                            Y.one('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class).removeClass('filter-hidden');
                            if (search_string === "") {
                                result.raw.node.removeClass('filter-show').addClass('show');
                                Y.one('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class).removeClass('filter-show').addClass('show');
                            } else {
                                result.raw.node.addClass('filter-show');
                                Y.one('ul.facetapi-facet-field-therapy-area-disease li#parent-' + element_class).addClass('filter-show');
                            }
                        }
                    }
                }
                Y.all('ul#ta-selector li#' + element_class).removeClass('quick_search_hidden');
            }
        });
    });
});